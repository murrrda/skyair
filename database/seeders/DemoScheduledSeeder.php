<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightTicket;
use App\Models\LoyaltyPoint;
use App\Models\Payment;
use App\Models\Plane;
use App\Models\Putnik;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\Route;
use App\Models\TicketClass;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates the DB with data tailored to the ticket-sales demo. It creates its
 * OWN flights (on dedicated "DEMO •" routes) at fixed offsets from now, so every
 * feature has a guaranteed target regardless of when the seeder runs:
 *
 *   DEMO-STALE-*  unpaid, created >24h ago, future flight  → reservations:cancel-expired
 *   DEMO-EXP-*    paid, future flight >3 days out          → cancel (UI) + loyalty:expire-points
 *   DEMO-FRESH-*  unpaid, just created                     → pay flow (kreirana, 24h rok)
 *   DEMO-USED-*   paid, past flight (already arrived)      → reservations:mark-used
 *   standalone reward points expiring in 5/14/28 days      → "Uskoro ističe" banner
 *
 * Idempotent: every run first wipes the previous DEMO-* data (reservations,
 * payments, loyalty rows and demo flights) so it never piles up.
 */
class DemoScheduledSeeder extends Seeder
{
    public function run(): void
    {
        $customer = $this->demoCustomer();
        $this->cleanupPreviousDemo($customer);
        $this->resetLoyaltyBalance($customer);

        $ekonom = TicketClass::where('name', 'Ekonomska')->first() ?? TicketClass::first();
        [$future, $past] = $this->seedDemoFlights();

        if ($future->isEmpty()) {
            $this->command->warn('Demo letovi nisu mogli da se naprave (nedostaje avion/dispečer/ruta). Pokreni glavni seeder prvo.');

            return;
        }

        $this->makeStaleUnpaid($customer, $future, $ekonom, 3);
        $this->makePaidWithExpiringPoints($customer, $future, $ekonom, 3);
        $this->makeFreshUnpaid($customer, $future, $ekonom);
        $this->makePastUsed($customer, $past, $ekonom, 3);
        $this->makeExpiringSoonPoints($customer);

        $this->command->info('Demo podaci spremni. Login: demo.kupac@skyair.rs / password');
        $this->command->line('   cancel-expired → DEMO-STALE  |  expire-points → DEMO-EXP  |  mark-used → DEMO-USED');
        $this->command->line('   pay flow → DEMO-FRESH  |  cancel (UI) → bilo koja DEMO-EXP (let > 3 dana)');
        $this->command->line('   pricing → demo:flight-tweak {id_buducег_leta} --occupancy= --season= --recompute');
    }

    private function demoCustomer(): User
    {
        return User::firstOrCreate(
            ['email' => 'demo.kupac@skyair.rs'],
            [
                'name' => 'Demo Kupac',
                'first_name' => 'Demo',
                'last_name' => 'Kupac',
                'password' => Hash::make('password'),
                'date_of_birth' => '1995-05-12',
                'address' => 'Beograd, Knez Mihailova 1',
                'phone_number' => '+381601234567',
            ],
        );
    }

    private function resetLoyaltyBalance(User $customer): void
    {
        $putnik = Putnik::firstOrCreate(
            ['user_id' => $customer->id],
            ['credit_card_number' => '4242424242424242'],
        );
        // Reset to a known baseline each run so loyalty:expire-points has room to
        // decrement and the demo is repeatable.
        $putnik->update(['status_points' => 12000, 'reward_points' => 8000, 'tier' => 'gold']);
    }

    /**
     * Remove everything a previous run created for the demo customer.
     */
    private function cleanupPreviousDemo(User $customer): void
    {
        DB::transaction(function () use ($customer) {
            $resIds = Reservation::where('user_id', $customer->id)
                ->where('code', 'like', 'DEMO-%')
                ->pluck('id');

            $paymentIds = Reservation::whereIn('id', $resIds)->pluck('payment_id')->filter();

            // Deleting a reservation cascades to flight_tickets + reservation_states
            // and nulls loyalty_points.reservation_id (per migration FK rules).
            Reservation::whereIn('id', $resIds)->delete();
            Payment::whereIn('id', $paymentIds)->delete();

            // Wipe all demo-customer loyalty rows (standalone + ledger entries).
            LoyaltyPoint::where('user_id', $customer->id)->delete();

            // Demo flights sit on dedicated "DEMO •" routes — drop them (and any
            // stray tickets) so flights don't accumulate across runs.
            $demoRouteIds = Route::where('name', 'like', 'DEMO •%')->pluck('id');
            $demoFlightIds = Flight::whereIn('route_id', $demoRouteIds)->pluck('id');
            FlightTicket::whereIn('flight_id', $demoFlightIds)->delete();
            Flight::whereIn('id', $demoFlightIds)->delete();
        });
    }

    /**
     * Create the demo flights and return [future, past] collections.
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function seedDemoFlights(): array
    {
        $planeId = Plane::value('id');
        $adminId = Route::value('admin_id');
        $dispatcherId = Flight::value('dispatcher_id') ?? $adminId;
        $begId = Airport::where('iata_code', 'BEG')->value('id')
            ?? Airport::where('city', 'Beograd')->value('id');

        if (! $planeId || ! $adminId || ! $dispatcherId || ! $begId) {
            return [collect(), collect()];
        }

        // Dedicated demo routes to real-ish destinations (reused across runs).
        $dests = [
            ['city' => 'Pariz', 'iata' => 'CDG', 'distance' => 1450],
            ['city' => 'Rim', 'iata' => 'FCO', 'distance' => 720],
            ['city' => 'Atina', 'iata' => 'ATH', 'distance' => 810],
            ['city' => 'Barselona', 'iata' => 'BCN', 'distance' => 1530],
        ];

        $routes = collect($dests)->map(function (array $d) use ($begId, $adminId) {
            $dest = Airport::where('city', $d['city'])->first()
                ?? Airport::create([
                    'iata_code' => $d['iata'],
                    'name' => $d['city'],
                    'city' => $d['city'],
                    'country' => '—',
                    'season_type' => 'none',
                ]);

            return Route::firstOrCreate(
                ['name' => 'DEMO • Beograd–'.$d['city']],
                [
                    'starting_airport_id' => $begId,
                    'landing_airport_id' => $dest->id,
                    'admin_id' => $adminId,
                    'distance_km' => $d['distance'],
                    'estimated_time' => max(60, (int) round($d['distance'] / 8)),
                    'active' => true,
                ],
            );
        })->values();

        // Future: all > 3 days out so cancellation is allowed; varied for search.
        $future = collect([5, 9, 16, 24])->map(function (int $days, int $i) use ($planeId, $routes, $dispatcherId) {
            $takeoff = now()->addDays($days)->setTime(8 + $i, 0);

            return Flight::create([
                'plane_id' => $planeId,
                'route_id' => $routes[$i % $routes->count()]->id,
                'dispatcher_id' => $dispatcherId,
                'expected_takeoff' => $takeoff,
                'expected_arrival' => $takeoff->copy()->addHours(2),
                'status' => 'scheduled',
                'base_price' => 12000 + $i * 2000,
            ]);
        });

        // Past: already arrived → mark-used targets.
        $past = collect([4, 12])->map(function (int $days, int $i) use ($planeId, $routes, $dispatcherId) {
            $takeoff = now()->subDays($days)->setTime(9, 0);

            return Flight::create([
                'plane_id' => $planeId,
                'route_id' => $routes[$i % $routes->count()]->id,
                'dispatcher_id' => $dispatcherId,
                'expected_takeoff' => $takeoff,
                'expected_arrival' => $takeoff->copy()->addHours(2),
                'status' => 'landed',
                'base_price' => 12000,
            ]);
        });

        return [$future, $past];
    }

    private function makeStaleUnpaid(User $customer, $flights, ?TicketClass $class, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $flight = $flights[$i % $flights->count()];
            $created = now()->subHours(25 + $i);

            $reservation = Reservation::create([
                'user_id' => $customer->id,
                'total_price' => 9000 + $i * 1500,
                'code' => 'DEMO-STALE-'.strtoupper(Str::random(4)),
            ]);
            $reservation->forceFill(['created_at' => $created, 'updated_at' => $created])->save();

            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'pending',
            ]);
            $state->forceFill(['created_at' => $created, 'updated_at' => $created])->save();
            $reservation->update(['latest_state_id' => $state->id]);

            FlightTicket::create([
                'passenger_first_name' => 'Demo',
                'passenger_last_name' => 'Putnik '.($i + 1),
                'flight_id' => $flight->id,
                'reservation_id' => $reservation->id,
                'ticket_class_id' => $class?->id ?? 1,
                'base_price' => 9000,
                'final_price' => 9000 + $i * 1500,
                'seat_number' => 50 + $i,
            ]);
        }
    }

    private function makePaidWithExpiringPoints(User $customer, $flights, ?TicketClass $class, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $flight = $flights[($i + 1) % $flights->count()];

            $bookedAt = now()->subDays(2);
            $paidAt = $bookedAt->copy()->addHours(2);

            $reservation = Reservation::create([
                'user_id' => $customer->id,
                'total_price' => 15000,
                'code' => 'DEMO-EXP-'.strtoupper(Str::random(4)),
            ]);
            $reservation->forceFill(['created_at' => $bookedAt, 'updated_at' => $paidAt])->save();

            $payment = Payment::create([
                'amount' => 15000,
                'method' => 'card',
                'status' => 'paid',
            ]);
            $payment->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->save();

            $reservation->update([
                'payment_id' => $payment->id,
                'latest_state_id' => $this->paidStateHistory($reservation, $bookedAt, $paidAt),
            ]);

            FlightTicket::create([
                'passenger_first_name' => 'Demo',
                'passenger_last_name' => 'Lojalan '.($i + 1),
                'flight_id' => $flight->id,
                'reservation_id' => $reservation->id,
                'ticket_class_id' => $class?->id ?? 1,
                'base_price' => 15000,
                'final_price' => 15000,
                'seat_number' => 70 + $i,
            ]);

            LoyaltyPoint::create([
                'user_id' => $customer->id,
                'reservation_id' => $reservation->id,
                'type' => 'reward',
                'action' => 'earned',
                'amount' => 1500,
                'description' => 'Demo: poeni za isteći — '.$reservation->code,
                'expires_at' => now()->subDay(),
            ]);
        }
    }

    private function makePastUsed(User $customer, $flights, ?TicketClass $class, int $count): void
    {
        if ($flights->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $flight = $flights[$i % $flights->count()];
            $bookedAt = $flight->expected_takeoff->copy()->subDays(7);
            $paidAt = $bookedAt->copy()->addHours(2);

            $reservation = Reservation::create([
                'user_id' => $customer->id,
                'total_price' => 12000 + $i * 2000,
                'code' => 'DEMO-USED-'.strtoupper(Str::random(4)),
            ]);
            $reservation->forceFill(['created_at' => $bookedAt, 'updated_at' => $paidAt])->save();

            $payment = Payment::create([
                'amount' => 12000 + $i * 2000,
                'method' => 'card',
                'status' => 'paid',
            ]);
            $payment->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->save();

            $reservation->update([
                'payment_id' => $payment->id,
                'latest_state_id' => $this->paidStateHistory($reservation, $bookedAt, $paidAt),
            ]);

            FlightTicket::create([
                'passenger_first_name' => 'Demo',
                'passenger_last_name' => 'Iskoriscena '.($i + 1),
                'flight_id' => $flight->id,
                'reservation_id' => $reservation->id,
                'ticket_class_id' => $class?->id ?? 1,
                'base_price' => 12000 + $i * 2000,
                'final_price' => 12000 + $i * 2000,
                'seat_number' => 30 + $i,
            ]);
        }
    }

    /**
     * Create the pending → confirmed state history for a paid reservation so the
     * timeline shows both "Rezervacija kreirana" and "Plaćanje potvrđeno".
     * Returns the id of the latest (confirmed) state.
     */
    private function paidStateHistory(Reservation $reservation, CarbonInterface $bookedAt, CarbonInterface $paidAt): int
    {
        $pending = ReservationState::create([
            'reservation_id' => $reservation->id,
            'status' => 'pending',
        ]);
        $pending->forceFill(['created_at' => $bookedAt, 'updated_at' => $bookedAt])->save();

        $confirmed = ReservationState::create([
            'reservation_id' => $reservation->id,
            'status' => 'confirmed',
        ]);
        $confirmed->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->save();

        return $confirmed->id;
    }

    /**
     * Standalone reward-points rows that expire within the next 30 days, so the
     * "Uskoro ističe" box on /kupac/loyalty has data to render.
     */
    private function makeExpiringSoonPoints(User $customer): void
    {
        $samples = [
            ['amount' => 800, 'expires_at' => now()->addDays(5)],
            ['amount' => 1200, 'expires_at' => now()->addDays(14)],
            ['amount' => 500, 'expires_at' => now()->addDays(28)],
        ];

        foreach ($samples as $i => $row) {
            LoyaltyPoint::create([
                'user_id' => $customer->id,
                'reservation_id' => null,
                'type' => 'reward',
                'action' => 'earned',
                'amount' => $row['amount'],
                'description' => 'Demo: poeni koji uskoro ističu #'.($i + 1),
                'expires_at' => $row['expires_at'],
            ]);
        }
    }

    private function makeFreshUnpaid(User $customer, $flights, ?TicketClass $class): void
    {
        $flight = $flights->first();
        $reservation = Reservation::create([
            'user_id' => $customer->id,
            'total_price' => 7500,
            'code' => 'DEMO-FRESH-'.strtoupper(Str::random(4)),
        ]);

        $state = ReservationState::create([
            'reservation_id' => $reservation->id,
            'status' => 'pending',
        ]);
        $reservation->update(['latest_state_id' => $state->id]);

        FlightTicket::create([
            'passenger_first_name' => 'Demo',
            'passenger_last_name' => 'Fresh',
            'flight_id' => $flight->id,
            'reservation_id' => $reservation->id,
            'ticket_class_id' => $class?->id ?? 1,
            'base_price' => 7500,
            'final_price' => 7500,
            'seat_number' => 99,
        ]);
    }
}
