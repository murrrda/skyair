<?php

namespace Database\Seeders;

use App\Models\Dodeljen;
use App\Models\Flight;
use App\Models\Plane;
use App\Models\Route;
use App\Models\Uloga;
use App\Models\Zaposlen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the performance report: a spread of completed (landed) flights
 * over the last six months with crew assigned, so the report shows real
 * numbers, weekday distribution, and monthly trends. Idempotent-ish: only runs
 * when there are very few landed flights already.
 */
class PerformanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Flight::doesntExist()) {
            $this->call(FlightSeeder::class);
        }

        if (Flight::where('status', 'landed')->count() >= 40) {
            $this->command?->warn('PerformanceDemoSeeder: landed flights already present, skipping.');

            return;
        }

        $routes = Route::pluck('id')->all();
        $planes = Plane::pluck('id')->all();
        $dispatcherId = DB::table('dispatchers')->value('user_id');

        if ($routes === [] || $planes === [] || $dispatcherId === null) {
            $this->command?->warn('PerformanceDemoSeeder: missing routes/planes/dispatcher, skipping.');

            return;
        }

        $uloge = Uloga::pluck('id', 'code');
        $pilots = Zaposlen::where('status', 'aktivan')->where('role', 'pilot')->pluck('user_id')->all();
        $coPilots = Zaposlen::where('status', 'aktivan')->where('role', 'co_pilot')->pluck('user_id')->all();
        $cabin = Zaposlen::where('status', 'aktivan')->where('role', 'cabin_crew')->pluck('user_id')->all();

        if ($pilots === [] || $cabin === []) {
            $this->command?->warn('PerformanceDemoSeeder: no crew available, skipping.');

            return;
        }

        $pi = $ci = $cabinI = 0;
        $created = 0;

        // ~90 flights spread across the last 180 days.
        for ($i = 0; $i < 90; $i++) {
            $takeoff = Carbon::now()
                ->subDays(rand(1, 178))
                ->setTime(rand(6, 20), [0, 15, 30, 45][rand(0, 3)]);
            $arrival = $takeoff->copy()->addMinutes(rand(75, 220));

            $flight = Flight::create([
                'plane_id' => $planes[array_rand($planes)],
                'route_id' => $routes[array_rand($routes)],
                'dispatcher_id' => $dispatcherId,
                'expected_takeoff' => $takeoff,
                'expected_arrival' => $arrival,
                'status' => 'landed',
                'crew_status' => 'staffed',
            ]);

            // Round-robin crew so the workload distributes across employees.
            $seats = [
                [$uloge['pilot'] ?? null, $pilots[$pi++ % count($pilots)]],
                [$uloge['co_pilot'] ?? null, ($coPilots !== [] ? $coPilots[$ci++ % count($coPilots)] : $pilots[$pi++ % count($pilots)])],
                [$uloge['cabin_crew'] ?? null, $cabin[$cabinI++ % count($cabin)]],
                [$uloge['cabin_crew'] ?? null, $cabin[$cabinI++ % count($cabin)]],
            ];

            foreach ($seats as [$ulogaId, $userId]) {
                if ($ulogaId === null) {
                    continue;
                }

                Dodeljen::firstOrCreate(
                    ['flight_id' => $flight->id, 'zaposlen_user_id' => $userId],
                    ['uloga_id' => $ulogaId, 'status' => 'confirmed'],
                );
            }

            $created++;
        }

        $this->command?->info("PerformanceDemoSeeder: created {$created} completed flights with crew.");
    }
}
