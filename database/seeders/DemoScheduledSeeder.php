<?php

namespace Database\Seeders;

use App\Models\Flight;
use App\Models\FlightTicket;
use App\Models\LoyaltyPoint;
use App\Models\Payment;
use App\Models\Putnik;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\TicketClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates the DB with data tailored to the scheduled-job demo:
 *   - 3 unpaid stale reservations (created >24h ago) → cancel-expired hits them
 *   - 3 paid reservations whose loyalty points expire today → expire-points hits them
 *   - 1 fresh unpaid reservation as a control (should NOT be cancelled)
 *
 * Idempotent: re-running won't duplicate the demo user; reservations are
 * fresh each run because they're tied to the current time.
 */
class DemoScheduledSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::firstOrCreate(
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

        Putnik::firstOrCreate(
            ['user_id' => $customer->id],
            [
                'credit_card_number' => '4242424242424242',
                'status_points' => 12000,
                'reward_points' => 8000,
                'tier' => 'gold',
            ],
        );

        $futureFlights = Flight::with(['plane', 'tickets'])
            ->where('expected_takeoff', '>', now())
            ->take(6)
            ->get();

        if ($futureFlights->isEmpty()) {
            $this->command->warn('No future flights in DB. Seed FlightSeeder first.');

            return;
        }

        $ekonom = TicketClass::where('name', 'Ekonomska')->first() ?? TicketClass::first();

        $pastFlights = Flight::with(['plane', 'tickets'])
            ->where('expected_takeoff', '<', now())
            ->orderByDesc('expected_takeoff')
            ->take(3)
            ->get();

        $this->makeStaleUnpaid($customer, $futureFlights, $ekonom, 3);
        $this->makePaidWithExpiringPoints($customer, $futureFlights, $ekonom, 3);
        $this->makeFreshUnpaid($customer, $futureFlights, $ekonom);
        $this->makePastUsed($customer, $pastFlights, $ekonom, 3);
        $this->makeExpiringSoonPoints($customer);

        $this->command->info('Demo data ready for: reservations:cancel-expired, loyalty:expire-points, flights:recompute-prices.');
        $this->command->line('   Login: demo.kupac@skyair.rs / password');
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

            $reservation = Reservation::create([
                'user_id' => $customer->id,
                'total_price' => 15000,
                'code' => 'DEMO-EXP-'.strtoupper(Str::random(4)),
            ]);

            $payment = Payment::create([
                'amount' => 15000,
                'method' => 'card',
                'status' => 'paid',
            ]);

            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'confirmed',
            ]);

            $reservation->update([
                'payment_id' => $payment->id,
                'latest_state_id' => $state->id,
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

            $reservation = Reservation::create([
                'user_id' => $customer->id,
                'total_price' => 12000 + $i * 2000,
                'code' => 'DEMO-USED-'.strtoupper(Str::random(4)),
            ]);
            $reservation->forceFill(['created_at' => $bookedAt, 'updated_at' => $bookedAt])->save();

            $payment = Payment::create([
                'amount' => 12000 + $i * 2000,
                'method' => 'card',
                'status' => 'paid',
            ]);
            $payment->forceFill(['created_at' => $bookedAt, 'updated_at' => $bookedAt])->save();

            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'confirmed',
            ]);
            $state->forceFill(['created_at' => $bookedAt, 'updated_at' => $bookedAt])->save();

            $reservation->update([
                'payment_id' => $payment->id,
                'latest_state_id' => $state->id,
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
     * Standalone reward-points rows that expire within the next 30 days, so
     * the "Uskoro ističe" box on /kupac/loyalty has data to render.
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
