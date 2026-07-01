<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Dodeljen;
use App\Models\EmployeeTraining;
use App\Models\Flight;
use App\Models\IncidentType;
use App\Models\Plane;
use App\Models\Route;
use App\Models\SeverityLevel;
use App\Models\TrainingType;
use App\Models\Uloga;
use App\Models\User;
use App\Models\Zaposlen;
use App\Services\IncidentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Heavy demo data so the performance & incident reports look full: a large
 * roster, hundreds of completed flights spread over six months, a few
 * deliberately overloaded employees (to exercise the near/over-limit bands),
 * and extra incidents. Additive but guarded so re-runs don't explode.
 */
class RichDemoSeeder extends Seeder
{
    private const FIRST = ['Marko', 'Nikola', 'Jovan', 'Ana', 'Ivana', 'Stefan', 'Milica', 'Petar', 'Jelena', 'Luka', 'Sara', 'Nemanja', 'Teodora', 'Vuk', 'Milan', 'Katarina', 'Đorđe', 'Marija', 'Filip', 'Tijana', 'Aleksa', 'Dunja', 'Bojan', 'Ivan', 'Sofija', 'Uroš', 'Anja', 'Lazar', 'Mina', 'Vladimir'];

    private const LAST = ['Jovanović', 'Nikolić', 'Petrović', 'Ilić', 'Marković', 'Đorđević', 'Stojanović', 'Pavlović', 'Kovačević', 'Lukić', 'Popović', 'Ristić', 'Todorović', 'Milošević', 'Simić', 'Lazić', 'Jović', 'Vasić', 'Savić', 'Radić'];

    public function run(): void
    {
        if (Flight::doesntExist()) {
            $this->call(FlightSeeder::class);
        }
        $this->call(CertificateTypeSeeder::class);
        $this->call(TrainingTypeSeeder::class);
        $this->call(UlogaSeeder::class);

        $certType = CertificateType::first();
        $trainingType = TrainingType::first();
        $uloge = Uloga::pluck('id', 'code');
        $dispatcherId = DB::table('dispatchers')->value('user_id');
        $routes = Route::pluck('id')->all();
        $planes = Plane::pluck('id')->all();

        if ($routes === [] || $planes === [] || $dispatcherId === null) {
            $this->command?->warn('RichDemoSeeder: missing routes/planes/dispatcher, skipping.');

            return;
        }

        // 1. Grow the roster.
        $this->ensureCrew('pilot', 10, $certType, $trainingType);
        $this->ensureCrew('co_pilot', 8, $certType, $trainingType);
        $this->ensureCrew('cabin_crew', 16, $certType, $trainingType);

        $pilots = Zaposlen::where('status', 'aktivan')->where('role', 'pilot')->pluck('user_id')->values()->all();
        $coPilots = Zaposlen::where('status', 'aktivan')->where('role', 'co_pilot')->pluck('user_id')->values()->all();
        $cabin = Zaposlen::where('status', 'aktivan')->where('role', 'cabin_crew')->pluck('user_id')->values()->all();

        // 2. Big batch of completed flights over the last 180 days.
        $made = 0;
        if (Flight::where('status', 'landed')->count() < 400) {
            $pi = $ci = $k = 0;
            DB::transaction(function () use (&$pi, &$ci, &$k, &$made, $planes, $routes, $dispatcherId, $uloge, $pilots, $coPilots, $cabin) {
                for ($i = 0; $i < 300; $i++) {
                    $t = Carbon::now()->subDays(rand(1, 178))->setTime(rand(6, 20), [0, 15, 30, 45][rand(0, 3)]);
                    $flight = $this->makeLandedFlight($planes, $routes, $dispatcherId, $t, $t->copy()->addMinutes(rand(75, 210)));

                    $this->assign($flight, $uloge, [
                        'pilot' => $pilots[$pi++ % count($pilots)],
                        'co_pilot' => $coPilots !== [] ? $coPilots[$ci++ % count($coPilots)] : $pilots[$pi++ % count($pilots)],
                        'cabin_crew_1' => $cabin[$k++ % count($cabin)],
                        'cabin_crew_2' => $cabin[$k++ % count($cabin)],
                    ]);
                    $made++;
                }
            });
        }

        // 3. Overload specific employees within one recent week so the report
        //    shows Prekoračenje / Blizu limita bands (5h flights, N per week).
        $weekStart = Carbon::now()->subDays(6)->startOfWeek();
        $bursts = [
            [$pilots[0] ?? null, 13],   // 65h -> Prekoračenje
            [$cabin[0] ?? null, 13],    // 65h -> Prekoračenje
            [$pilots[1] ?? null, 11],   // 55h -> Blizu limita
            [$coPilots[0] ?? null, 11], // 55h -> Blizu limita
        ];
        foreach ($bursts as [$uid, $count]) {
            if ($uid === null) {
                continue;
            }
            $code = Zaposlen::where('user_id', $uid)->value('role') ?? 'cabin_crew';
            for ($j = 0; $j < $count; $j++) {
                $t = $weekStart->copy()->addDays($j % 7)->setTime(5 + ($j % 3) * 5, 0);
                $flight = $this->makeLandedFlight($planes, $routes, $dispatcherId, $t, $t->copy()->addHours(5));
                Dodeljen::firstOrCreate(
                    ['flight_id' => $flight->id, 'zaposlen_user_id' => $uid],
                    ['uloga_id' => $uloge[$code] ?? $uloge['cabin_crew'], 'status' => 'confirmed'],
                );
            }
        }

        // 4. More incidents (spread across recent completed flights).
        $this->seedIncidents($pilots, $coPilots, $cabin);

        $this->command?->info("RichDemoSeeder: +{$made} flights, roster grown, bursts + incidents added.");
    }

    private function ensureCrew(string $role, int $target, ?CertificateType $certType, ?TrainingType $trainingType): void
    {
        $existing = Zaposlen::where('role', $role)->where('status', 'aktivan')->count();

        for ($i = $existing; $i < $target; $i++) {
            $first = self::FIRST[array_rand(self::FIRST)];
            $last = self::LAST[array_rand(self::LAST)];

            $user = User::factory()->create([
                'first_name' => $first,
                'last_name' => $last,
                'name' => "{$first} {$last}",
                'email' => "{$role}{$i}@skyair.demo",
                'password' => Hash::make('password'),
                'date_of_birth' => Carbon::now()->subYears(rand(25, 55))->toDateString(),
                'address' => 'Beograd',
            ]);

            Zaposlen::create([
                'user_id' => $user->id,
                'role' => $role,
                'datum_zaposlenja' => Carbon::now()->subMonths(rand(6, 60))->toDateString(),
                'status' => 'aktivan',
            ]);

            if ($certType) {
                Certificate::create([
                    'zaposlen_user_id' => $user->id,
                    'certificate_type_id' => $certType->id,
                    'issued_at' => Carbon::now()->subMonths(6),
                    'expires_at' => Carbon::now()->addYears(2),
                ]);
            }
            if ($trainingType) {
                EmployeeTraining::create([
                    'zaposlen_user_id' => $user->id,
                    'training_type_id' => $trainingType->id,
                    'started_at' => Carbon::now()->subMonths(7),
                    'finished_at' => Carbon::now()->subMonths(6),
                ]);
            }
        }
    }

    /**
     * @param  array<int, int>  $planes
     * @param  array<int, int>  $routes
     */
    private function makeLandedFlight(array $planes, array $routes, int $dispatcherId, Carbon $takeoff, Carbon $arrival): Flight
    {
        return Flight::create([
            'plane_id' => $planes[array_rand($planes)],
            'route_id' => $routes[array_rand($routes)],
            'dispatcher_id' => $dispatcherId,
            'expected_takeoff' => $takeoff,
            'expected_arrival' => $arrival,
            'status' => 'landed',
            'crew_status' => 'staffed',
        ]);
    }

    /**
     * @param  array<string, int|null>  $crew  keyed by seat
     */
    private function assign(Flight $flight, Collection $uloge, array $crew): void
    {
        $seats = [
            ['pilot', $crew['pilot'] ?? null],
            ['co_pilot', $crew['co_pilot'] ?? null],
            ['cabin_crew', $crew['cabin_crew_1'] ?? null],
            ['cabin_crew', $crew['cabin_crew_2'] ?? null],
        ];

        foreach ($seats as [$code, $uid]) {
            if ($uid === null || ! isset($uloge[$code])) {
                continue;
            }
            Dodeljen::firstOrCreate(
                ['flight_id' => $flight->id, 'zaposlen_user_id' => $uid],
                ['uloga_id' => $uloge[$code], 'status' => 'confirmed'],
            );
        }
    }

    /**
     * @param  array<int, int>  $pilots
     * @param  array<int, int>  $coPilots
     * @param  array<int, int>  $cabin
     */
    private function seedIncidents(array $pilots, array $coPilots, array $cabin): void
    {
        $types = IncidentType::pluck('id')->all();
        $severities = SeverityLevel::pluck('id')->all();
        $landed = Flight::where('status', 'landed')->inRandomOrder()->limit(30)->pluck('id')->all();

        if ($types === [] || $severities === [] || $landed === []) {
            return;
        }

        $everyone = array_merge($pilots, $coPilots, $cabin);
        $service = app(IncidentService::class);

        foreach ($landed as $i => $flightId) {
            $responsible = [];
            if (rand(0, 100) < 70 && $everyone !== []) {
                $responsible[] = $everyone[array_rand($everyone)];
            }

            $service->record([
                'flight_id' => $flightId,
                'incident_type_id' => $types[array_rand($types)],
                'severity_level_id' => $severities[array_rand($severities)],
                'occurred_at' => Carbon::now()->subDays(rand(1, 55))->setTime(rand(6, 22), rand(0, 59)),
                'description' => 'Automatski generisan incident za potrebe demonstracije izveštaja.',
            ], $responsible);
        }
    }
}
