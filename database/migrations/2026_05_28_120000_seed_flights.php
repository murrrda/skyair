<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('zaposleni')->where('role', 'admin')->value('user_id');
        if ($adminId === null) {
            $adminId = $this->createEmployee('Seed', 'Admin', 'seed-admin@skyair.local', 'admin');
        }

        $dispatcherId = DB::table('zaposleni')->where('role', 'dispatcher')->value('user_id');
        if ($dispatcherId === null) {
            $dispatcherId = $this->createEmployee('Seed', 'Dispatcher', 'seed-dispatcher@skyair.local', 'dispatcher');
        }

        DB::table('dispatchers')->insertOrIgnore([
            'user_id' => $dispatcherId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planeId = DB::table('planes')->value('id');
        if ($planeId === null) {
            $planeId = DB::table('planes')->insertGetId([
                'reg_number' => 90001,
                'admin_id' => $adminId,
                'model' => 'Airbus A320',
                'capacity' => 180,
                'luxury_level' => 3,
                'range_km' => 6100,
                'max_speed' => 833,
                'repair_service_interval' => 500,
                'model_year' => 2018,
                'status' => 'in_garage',
                'commissioned_at' => now()->subYears(5),
                'total_mileage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $airportCodes = ['BEG', 'CDG', 'FRA', 'MUC', 'VIE', 'AMS'];
        $airports = DB::table('airports')
            ->whereIn('iata_code', $airportCodes)
            ->pluck('id', 'iata_code');

        $legs = [
            ['BEG', 'CDG', 'Beograd → Pariz', 1450, 145],
            ['BEG', 'FRA', 'Beograd → Frankfurt', 1090, 120],
            ['BEG', 'MUC', 'Beograd → Minhen', 900, 110],
            ['BEG', 'VIE', 'Beograd → Beč', 500, 75],
            ['BEG', 'AMS', 'Beograd → Amsterdam', 1500, 150],
        ];

        $routeIds = [];
        foreach ($legs as [$from, $to, $name, $km, $minutes]) {
            $routeIds[] = DB::table('routes')->insertGetId([
                'starting_airport_id' => $airports[$from],
                'landing_airport_id' => $airports[$to],
                'admin_id' => $adminId,
                'name' => $name,
                'distance_km' => $km,
                'estimated_time' => $minutes,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $base = now()->addDays(2)->setTime(8, 0);
        foreach ($routeIds as $index => $routeId) {
            $leg = $legs[$index];
            $takeoff = $base->copy()->addDays($index)->addHours($index * 2);
            $arrival = $takeoff->copy()->addMinutes($leg[4]);

            DB::table('flights')->insert([
                'plane_id' => $planeId,
                'route_id' => $routeId,
                'dispatcher_id' => $dispatcherId,
                'expected_takeoff' => $takeoff,
                'expected_arrival' => $arrival,
                'status' => 'scheduled',
                'longitude' => null,
                'latitude' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Seed data only; nothing to roll back individually.
    }

    private function createEmployee(string $first, string $last, string $email, string $role): int
    {
        $userId = DB::table('users')->insertGetId([
            'first_name' => $first,
            'last_name' => $last,
            'name' => $first.' '.$last,
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zaposleni')->insert([
            'user_id' => $userId,
            'role' => $role,
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tipId = DB::table('tipovi_ugovora')->value('id');
        if ($tipId !== null) {
            DB::table('ugovori')->insert([
                'zaposlen_user_id' => $userId,
                'tip_ugovora_id' => $tipId,
                'datum_potpisivanja' => now()->toDateString(),
                'datum_isteka' => null,
                'napomena' => 'Seeded',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $userId;
    }
};
