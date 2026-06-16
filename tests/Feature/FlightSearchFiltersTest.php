<?php

use App\Models\Airport;
use App\Models\Flight;
use App\Models\Layover;
use App\Models\Plane;
use App\Models\Route;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Build a fully persisted future flight on a fresh route. Returns the Flight
 * so individual tests can attach layovers or assert against it.
 */
function searchableFlight(Carbon $takeoff, ?Airport $from = null, ?Airport $to = null): Flight
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
        'capacity' => 100,
        'luxury_level' => 2,
        'range_km' => 5000,
        'max_speed' => 800,
        'repair_service_interval' => 500,
        'model_year' => 2022,
        'status' => 'in_garage',
    ]);

    $from ??= Airport::create(['iata_code' => 'F'.random_int(10, 99), 'name' => 'From', 'city' => 'Beograd', 'country' => 'RS', 'season_type' => 'none']);
    $to ??= Airport::create(['iata_code' => 'T'.random_int(10, 99), 'name' => 'To', 'city' => 'Pariz', 'country' => 'FR', 'season_type' => 'none']);

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

test('time of day filter keeps only morning flights when morning slot is selected', function () {
    $morning = searchableFlight(Carbon::now()->addDay()->setTime(8, 0));
    $evening = searchableFlight(Carbon::now()->addDay()->setTime(20, 0));

    $response = $this->get('/kupac/rezultati-pretrage?time_of_day[]=morning');

    $response->assertOk();
    $ids = collect($response->viewData('page')['props']['flights'])->pluck('id')->all();

    expect($ids)->toContain($morning->id)->not->toContain($evening->id);
});

test('time of day filter accepts multiple slots', function () {
    $morning = searchableFlight(Carbon::now()->addDay()->setTime(8, 0));
    $afternoon = searchableFlight(Carbon::now()->addDay()->setTime(14, 0));
    $evening = searchableFlight(Carbon::now()->addDay()->setTime(20, 0));

    $response = $this->get('/kupac/rezultati-pretrage?time_of_day[]=morning&time_of_day[]=evening');

    $ids = collect($response->viewData('page')['props']['flights'])->pluck('id')->all();

    expect($ids)
        ->toContain($morning->id)
        ->toContain($evening->id)
        ->not->toContain($afternoon->id);
});

test('stops=direct excludes flights with layovers', function () {
    $direct = searchableFlight(Carbon::now()->addDay()->setTime(10, 0));
    $connecting = searchableFlight(Carbon::now()->addDay()->setTime(11, 0));

    $stopover = Airport::create(['iata_code' => 'L'.random_int(10, 99), 'name' => 'Stop', 'city' => 'Beč', 'country' => 'AT', 'season_type' => 'none']);
    Layover::create([
        'route_id' => $connecting->route_id,
        'airport_id' => $stopover->id,
        'stop_order' => 1,
        'expected_stay' => 60,
    ]);

    $response = $this->get('/kupac/rezultati-pretrage?stops=direct');

    $ids = collect($response->viewData('page')['props']['flights'])->pluck('id')->all();

    expect($ids)->toContain($direct->id)->not->toContain($connecting->id);
});

test('stops=connecting excludes direct flights', function () {
    $direct = searchableFlight(Carbon::now()->addDay()->setTime(10, 0));
    $connecting = searchableFlight(Carbon::now()->addDay()->setTime(11, 0));

    $stopover = Airport::create(['iata_code' => 'L'.random_int(10, 99), 'name' => 'Stop', 'city' => 'Beč', 'country' => 'AT', 'season_type' => 'none']);
    Layover::create([
        'route_id' => $connecting->route_id,
        'airport_id' => $stopover->id,
        'stop_order' => 1,
        'expected_stay' => 60,
    ]);

    $response = $this->get('/kupac/rezultati-pretrage?stops=connecting');

    $ids = collect($response->viewData('page')['props']['flights'])->pluck('id')->all();

    expect($ids)->toContain($connecting->id)->not->toContain($direct->id);
});

test('price range filters against the selected class price', function () {
    // Both flights have an economy of ~5000; business ~7500; first ~10000.
    $cheap = searchableFlight(Carbon::now()->addDay()->setTime(9, 0));
    $cheap->update(['current_price' => 5000, 'base_price' => 5000, 'price_updated_at' => now()]);
    $expensive = searchableFlight(Carbon::now()->addDay()->setTime(10, 0));
    $expensive->update(['current_price' => 9000, 'base_price' => 9000, 'price_updated_at' => now()]);

    // Filtering by economy price between 0 and 6000 keeps only the cheap one.
    $response = $this->get('/kupac/rezultati-pretrage?class=ekonom&price_max=6000');
    $ids = collect($response->viewData('page')['props']['flights'])->pluck('id')->all();
    expect($ids)->toContain($cheap->id)->not->toContain($expensive->id);

    // Filtering by first-class price <= 11000 — first = economy * 2 — only cheap (10000) fits.
    $response = $this->get('/kupac/rezultati-pretrage?class=prva&price_max=11000');
    $ids = collect($response->viewData('page')['props']['flights'])->pluck('id')->all();
    expect($ids)->toContain($cheap->id)->not->toContain($expensive->id);
});

test('return_date triggers a second search with swapped origin and destination', function () {
    $beograd = Airport::create(['iata_code' => 'BEG', 'name' => 'Beograd', 'city' => 'Beograd', 'country' => 'RS', 'season_type' => 'none']);
    $pariz = Airport::create(['iata_code' => 'CDG', 'name' => 'Pariz', 'city' => 'Pariz', 'country' => 'FR', 'season_type' => 'none']);

    $outbound = searchableFlight(Carbon::now()->addDays(7)->setTime(10, 0), $beograd, $pariz);
    $returnLeg = searchableFlight(Carbon::now()->addDays(14)->setTime(18, 0), $pariz, $beograd);

    $response = $this->get(sprintf(
        '/kupac/rezultati-pretrage?from=Beograd&to=Pariz&date=%s&return_date=%s',
        $outbound->expected_takeoff->toDateString(),
        $returnLeg->expected_takeoff->toDateString(),
    ));

    $props = $response->viewData('page')['props'];
    $outboundIds = collect($props['flights'])->pluck('id')->all();
    $returnIds = collect($props['return_flights'])->pluck('id')->all();

    expect($outboundIds)->toContain($outbound->id)->not->toContain($returnLeg->id);
    expect($returnIds)->toContain($returnLeg->id)->not->toContain($outbound->id);
});
