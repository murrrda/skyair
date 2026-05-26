<?php

namespace App\Console\Commands;

use App\Models\LoyaltyPoint;
use App\Models\Putnik;
use App\Models\Reservation;
use App\Models\ReservationState;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reservations:cancel-expired')]
#[Description('Otkazuje neplaćene rezervacije starije od 24h')]
class CancelExpiredReservations extends Command
{
    public function handle(): int
    {
        $expired = Reservation::whereHas('latestState', fn ($q) => $q->where('status', 'pending'))
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
        }

        $this->info("Otkazano {$expired->count()} isteklih rezervacija.");

        return self::SUCCESS;
    }
}
