<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\Plane;
use App\Models\Route;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AirportSeeder::class);

        $adminUser = User::create([
            'name' => 'Admin Dispatcher',
            'email' => 'admin@skyair.rs',
            'password' => Hash::make('password'),
            'first_name' => 'Admin',
            'last_name' => 'Dispatcher',
            'date_of_birth' => '1985-03-15',
            'address' => 'Beograd',
        ]);

        Zaposlen::create([
            'user_id' => $adminUser->id,
            'role' => 'dispatcher',
            'datum_zaposlenja' => '2024-01-01',
            'status' => 'aktivan',
        ]);

        DB::table('dispatchers')->insert([
            'user_id' => $adminUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planes = [
            ['reg_number' => 10001, 'model' => 'Boeing 737-800',    'capacity' => 189, 'luxury_level' => 2, 'range_km' => 5765,  'max_speed' => 842, 'repair_service_interval' => 500, 'model_year' => 2020],
            ['reg_number' => 10002, 'model' => 'Airbus A320neo',    'capacity' => 180, 'luxury_level' => 2, 'range_km' => 6300,  'max_speed' => 833, 'repair_service_interval' => 500, 'model_year' => 2022],
            ['reg_number' => 10003, 'model' => 'Airbus A330-300',   'capacity' => 277, 'luxury_level' => 3, 'range_km' => 11750, 'max_speed' => 871, 'repair_service_interval' => 600, 'model_year' => 2019],
            ['reg_number' => 10004, 'model' => 'Boeing 787-9',      'capacity' => 296, 'luxury_level' => 4, 'range_km' => 14140, 'max_speed' => 903, 'repair_service_interval' => 700, 'model_year' => 2023],
        ];

        foreach ($planes as $p) {
            Plane::create(array_merge($p, [
                'admin_id' => $adminUser->id,
                'status' => 'in_garage',
            ]));
        }

        $beg = Airport::where('iata_code', 'BEG')->first();
        $cdg = Airport::where('iata_code', 'CDG')->first();
        $fra = Airport::where('iata_code', 'FRA')->first();
        $vie = Airport::where('iata_code', 'VIE')->first();
        $ath = Airport::where('iata_code', 'ATH')->first();
        $ist = Airport::where('iata_code', 'IST')->first();
        $ams = Airport::where('iata_code', 'AMS')->first();
        $muc = Airport::where('iata_code', 'MUC')->first();

        $routeData = [
            ['from' => $beg, 'to' => $cdg, 'name' => 'Beograd – Pariz',     'distance_km' => 1860, 'estimated_time' => 165],
            ['from' => $beg, 'to' => $fra, 'name' => 'Beograd – Frankfurt',  'distance_km' => 1300, 'estimated_time' => 130],
            ['from' => $beg, 'to' => $vie, 'name' => 'Beograd – Beč',        'distance_km' => 600,  'estimated_time' => 75],
            ['from' => $beg, 'to' => $ath, 'name' => 'Beograd – Atina',      'distance_km' => 1100, 'estimated_time' => 110],
            ['from' => $beg, 'to' => $ist, 'name' => 'Beograd – Istanbul',   'distance_km' => 950,  'estimated_time' => 100],
            ['from' => $beg, 'to' => $ams, 'name' => 'Beograd – Amsterdam',  'distance_km' => 1750, 'estimated_time' => 155],
            ['from' => $beg, 'to' => $muc, 'name' => 'Beograd – Minhen',     'distance_km' => 950,  'estimated_time' => 105],
            ['from' => $cdg, 'to' => $beg, 'name' => 'Pariz – Beograd',      'distance_km' => 1860, 'estimated_time' => 165],
            ['from' => $vie, 'to' => $beg, 'name' => 'Beč – Beograd',        'distance_km' => 600,  'estimated_time' => 75],
            ['from' => $ath, 'to' => $beg, 'name' => 'Atina – Beograd',      'distance_km' => 1100, 'estimated_time' => 110],
        ];

        $routes = [];
        foreach ($routeData as $r) {
            $routes[] = Route::create([
                'starting_airport_id' => $r['from']->id,
                'landing_airport_id' => $r['to']->id,
                'admin_id' => $adminUser->id,
                'name' => $r['name'],
                'distance_km' => $r['distance_km'],
                'estimated_time' => $r['estimated_time'],
                'active' => true,
            ]);
        }

        $allPlanes = Plane::all();
        $baseDate = Carbon::parse('2026-06-01 06:00:00');

        foreach ($routes as $i => $route) {
            $flightsPerRoute = rand(3, 5);
            for ($j = 0; $j < $flightsPerRoute; $j++) {
                $takeoff = $baseDate->copy()
                    ->addDays($j * rand(2, 4))
                    ->addHours(rand(0, 14));
                $arrival = $takeoff->copy()->addMinutes($route->estimated_time);
                $plane = $allPlanes[$i % $allPlanes->count()];

                Flight::create([
                    'plane_id' => $plane->id,
                    'route_id' => $route->id,
                    'dispatcher_id' => $adminUser->id,
                    'expected_takeoff' => $takeoff,
                    'expected_arrival' => $arrival,
                    'status' => 'scheduled',
                ]);
            }
        }
    }
}
