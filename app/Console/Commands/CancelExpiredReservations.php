<?php

namespace App\Console\Commands;

use App\Models\EmailQueue;
use App\Models\LoyaltyPoint;
use App\Models\Putnik;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Notifications\ReservationAutoCancelled;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('reservations:cancel-expired')]
#[Description('Otkazuje neplaćene rezervacije starije od 24h')]
class CancelExpiredReservations extends Command
{
    public function handle(): int
    {
        $expired = Reservation::with('user')
            ->whereHas('latestState', fn ($q) => $q->where('status', 'pending'))
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($expired as $reservation) {
            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'cancelled',
            ]);
            $reservation->update(['latest_state_id' => $state->id]);

            $spent = LoyaltyPoint::where('reservation_id', $reservation->id)
                ->where('type', 'reward')
                ->where('action', 'spent')
                ->sum('amount');

            if ($spent > 0) {
                $putnik = Putnik::find($reservation->user_id);
                $putnik?->increment('reward_points', $spent);

                LoyaltyPoint::create([
                    'user_id' => $reservation->user_id,
                    'reservation_id' => $reservation->id,
                    'type' => 'reward',
                    'action' => 'earned',
                    'amount' => $spent,
                    'description' => 'Povrat poena — istekla rezervacija '.$reservation->code,
                ]);
            }

            $this->notifyCustomer($reservation);
        }

        $this->info("Otkazano {$expired->count()} isteklih rezervacija.");

        return self::SUCCESS;
    }

    /**
     * Notify the customer that their reservation was cancelled for non-payment.
     * Each channel is isolated so a delivery failure never aborts the run.
     */
    private function notifyCustomer(Reservation $reservation): void
    {
        $user = $reservation->user;

        if (! $user) {
            return;
        }

        try {
            $user->notify(new ReservationAutoCancelled($reservation));
        } catch (Throwable $e) {
            $this->warn("In-app obaveštenje nije poslato za {$reservation->code}: {$e->getMessage()}");
        }

        try {
            EmailQueue::create([
                'recipient_email' => (string) $user->email,
                'subject' => 'SkyAir — otkazana rezervacija '.$reservation->code,
                'body' => $this->buildEmailBody($reservation, $user->first_name ?? $user->name),
            ]);
        } catch (Throwable $e) {
            $this->warn("Email nije zakazan za {$reservation->code}: {$e->getMessage()}");
        }
    }

    private function buildEmailBody(Reservation $reservation, ?string $name): string
    {
        $lines = [
            'Poštovani '.($name ?: 'korisniče').',',
            '',
            "Vaša rezervacija {$reservation->code} je otkazana jer nije plaćena u roku od 24 sata od kreiranja.",
            '',
            'Sedišta su ponovo puštena u prodaju. Ako i dalje želite da putujete, slobodno napravite novu rezervaciju.',
            '',
            'Hvala što koristite SkyAir.',
        ];

        return implode("\n", $lines);
    }
}
