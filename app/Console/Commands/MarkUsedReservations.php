<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\ReservationState;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reservations:mark-used')]
#[Description('Označava plaćene rezervacije čiji je let završen kao "iskorišćene" (completed)')]
class MarkUsedReservations extends Command
{
    public function handle(): int
    {
        // Paid (confirmed) reservations whose flight has already arrived count
        // as "used" — the passenger has flown to the destination. We persist the
        // state transition instead of deriving it on the fly so the status is
        // auditable and shows up in the reservation timeline. Pending/cancelled
        // reservations are never touched: an unpaid or cancelled ticket cannot
        // be "used".
        $reservations = Reservation::query()
            ->whereHas('latestState', fn ($q) => $q->where('status', 'confirmed'))
            ->whereHas('tickets.flight', fn ($q) => $q->where('expected_arrival', '<', now()))
            ->get();

        foreach ($reservations as $reservation) {
            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'completed',
            ]);
            $reservation->update(['latest_state_id' => $state->id]);
        }

        $this->info("Označeno {$reservations->count()} iskorišćenih rezervacija.");

        return self::SUCCESS;
    }
}
