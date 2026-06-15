<?php

use App\Models\Airport;
use App\Models\Flight;
use App\Models\Plane;
use App\Models\Route;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Build a complete, persisted flight graph (mirrors FlightSeeder) with a
 * destination of the given season type.
 */
function pricedFlight(string $destinationSeason, Carbon $takeoff, int $capacity = 100): Flight
{
    $admin = User::create([
        'name' => 'Disp '.uniqid(),
        'email' => 'disp_'.uniqid().'@skyair.rs',
        'password' => Hash::make('password'),
        'first_name' => 'Disp',
        'last_name' => 'Atcher',
        'date_of_birth' => '1985-03-15',
        'address' => 'Beograd',
    ]);

    Zaposlen::create([
        'user_id' => $admin->id,
        'role' => 'dispatcher',
        'datum_zaposlenja' => '2024-01-01',
        'status' => 'aktivan',
    ]);

    DB::table('dispatchers')->insert([
        'user_id' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $plane = Plane::create([
        'reg_number' => random_int(20000, 99999),
        'admin_id' => $admin->id,
        'model' => 'Test Plane',
        'capacity' => $capacity,
        'luxury_level' => 2,
        'range_km' => 5000,
        'max_speed' => 800,
        'repair_service_interval' => 500,
        'model_year' => 2022,
        'status' => 'in_garage',
    ]);

    $from = Airport::create(['iata_code' => 'F'.random_int(10, 99), 'name' => 'From', 'city' => 'From', 'country' => 'X', 'season_type' => 'none']);
    $to = Airport::create(['iata_code' => 'T'.random_int(10, 99), 'name' => 'To', 'city' => 'To', 'country' => 'Y', 'season_type' => $destinationSeason]);

    $route = Route::create([
        'starting_airport_id' => $from->id,
        'landing_airport_id' => $to->id,
        'admin_id' => $admin->id,
        'name' => 'Test Route',
        'distance_km' => 500,
        'estimated_time' => 90,
        'active' => true,
    ]);

    return Flight::create([
        'plane_id' => $plane->id,
        'route_id' => $route->id,
        'dispatcher_id' => $admin->id,
        'expected_takeoff' => $takeoff,
        'expected_arrival' => $takeoff->copy()->addHours(2),
        'status' => 'scheduled',
    ]);
}

test('recompute persists a dynamic price for a future flight', function () {
    // 500km -> base 5000; summer destination in July -> 1.18; 0 sold -> 0.90
    $flight = pricedFlight('summer', Carbon::create(2026, 7, 15, 12), 100);

    $this->artisan('flights:recompute-prices')->assertSuccessful();
    $flight->refresh();

    expect((float) $flight->base_price)->toBe(5000.0)
        ->and((float) $flight->current_price)->toBe(5310.0) // 5000 * 0.90 * 1.18
        ->and($flight->price_updated_at)->not->toBeNull();
});

test('recompute leaves recently priced flights alone unless forced', function () {
    $flight = pricedFlight('none', Carbon::create(2026, 9, 15, 12), 100);

    $this->artisan('flights:recompute-prices')->assertSuccessful();
    $original = (float) $flight->refresh()->current_price; // 5000 * 0.90 = 4500

    // Tamper with the price while keeping price_updated_at fresh.
    $flight->update(['current_price' => 1]);

    $this->artisan('flights:recompute-prices')->assertSuccessful();
    expect((float) $flight->fresh()->current_price)->toBe(1.0); // skipped, still within interval

    $this->artisan('flights:recompute-prices --force')->assertSuccessful();
    expect((float) $flight->fresh()->current_price)->toBe($original); // forced recompute
});
