<?php

use App\Models\Airport;
use App\Models\Cancellation;
use App\Models\Flight;
use App\Models\FlightTicket;
use App\Models\Plane;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\Route;
use App\Models\TicketClass;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Build a confirmed single-ticket reservation for a fresh customer on a flight
 * departing $daysUntilFlight days from now. Returns [customer, reservation].
 *
 * @return array{0: User, 1: Reservation}
 */
function cancelScenario(int $daysUntilFlight): array
{
    $admin = User::create([
        'name' => 'Adm '.uniqid(),
        'email' => 'adm_'.uniqid().'@skyair.local',
        'password' => Hash::make('password'),
        'first_name' => 'Adm',
        'last_name' => 'In',
        'date_of_birth' => '1980-01-01',
        'address' => 'Beograd',
        'email_verified_at' => now(),
    ]);
    Zaposlen::create(['user_id' => $admin->id, 'role' => 'dispatcher', 'datum_zaposlenja' => '2024-01-01', 'status' => 'aktivan']);
    DB::table('dispatchers')->insert(['user_id' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);

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
    $from = Airport::create(['iata_code' => 'F'.random_int(10, 99), 'name' => 'From', 'city' => 'From', 'country' => 'X', 'season_type' => 'none']);
    $to = Airport::create(['iata_code' => 'T'.random_int(10, 99), 'name' => 'To', 'city' => 'To', 'country' => 'Y', 'season_type' => 'none']);
    $route = Route::create([
        'starting_airport_id' => $from->id,
        'landing_airport_id' => $to->id,
        'admin_id' => $admin->id,
        'name' => 'Test Route',
        'distance_km' => 500,
        'estimated_time' => 90,
        'active' => true,
    ]);
    $takeoff = now()->addDays($daysUntilFlight)->setTime(10, 0);
    $flight = Flight::create([
        'plane_id' => $plane->id,
        'route_id' => $route->id,
        'dispatcher_id' => $admin->id,
        'expected_takeoff' => $takeoff,
        'expected_arrival' => $takeoff->copy()->addHours(2),
        'status' => 'scheduled',
    ]);

    $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

    // Customer = a user with no employee record (passes the is-kupac gate).
    $customer = User::create([
        'name' => 'Kup '.uniqid(),
        'email' => 'kup_'.uniqid().'@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Kup',
        'last_name' => 'Ac',
        'date_of_birth' => '1990-01-01',
        'address' => 'Beograd',
        'email_verified_at' => now(),
    ]);
    $reservation = Reservation::create([
        'user_id' => $customer->id,
        'total_price' => 10000,
        'code' => 'SA-'.strtoupper(substr(uniqid(), -6)),
    ]);
    $state = ReservationState::create(['reservation_id' => $reservation->id, 'status' => 'confirmed']);
    $reservation->update(['latest_state_id' => $state->id]);
    FlightTicket::create([
        'passenger_first_name' => 'Kup',
        'passenger_last_name' => 'Ac',
        'flight_id' => $flight->id,
        'reservation_id' => $reservation->id,
        'ticket_class_id' => $class->id,
        'base_price' => 8000,
        'final_price' => 10000,
        'seat_number' => 1,
    ]);

    return [$customer, $reservation];
}

test('customer can cancel more than 3 days before departure', function () {
    [$customer, $reservation] = cancelScenario(5);

    $this->actingAs($customer)
        ->post("/kupac/otkazivanje-rezervacije/{$reservation->id}", ['reason' => 'promena_planova'])
        ->assertRedirect(route('kupac.moji-letovi'));

    expect($reservation->fresh()->latestState->status)->toBe('cancelled')
        ->and($reservation->tickets()->count())->toBe(1) // soft-cancel: tickets remain for history
        ->and(Cancellation::where('reason', 'promena_planova')->exists())->toBeTrue();
});

test('customer cannot cancel within 3 days of departure', function () {
    [$customer, $reservation] = cancelScenario(2);

    $this->actingAs($customer)
        ->post("/kupac/otkazivanje-rezervacije/{$reservation->id}", ['reason' => 'promena_planova'])
        ->assertRedirect(route('kupac.detalji-rezervacije', $reservation))
        ->assertSessionHas('error');

    expect($reservation->fresh()->latestState->status)->toBe('confirmed')
        ->and($reservation->tickets()->count())->toBe(1);
});
