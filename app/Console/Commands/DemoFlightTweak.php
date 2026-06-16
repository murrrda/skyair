<?php

namespace App\Console\Commands;

use App\Models\Flight;
use App\Models\FlightTicket;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\TicketClass;
use App\Services\PricingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('demo:flight-tweak {flight_id : Flight ID to tweak} {--capacity= : Set the plane capacity (sedišta)} {--occupancy= : Target occupancy percent (0-100)} {--season= : Destination season type (summer|winter|none)} {--recompute : Run recompute-prices --force after the tweak}')]
#[Description('Demo helper za dynamic pricing: menja capacity, popunjenost (dummy karte) i sezonu destinacije, opciono okine recompute')]
class DemoFlightTweak extends Command
{
    public function handle(PricingService $pricing): int
    {
        $flight = Flight::with(['plane', 'tickets', 'route.landingAirport'])->find($this->argument('flight_id'));
        if (! $flight) {
            $this->error("Let #{$this->argument('flight_id')} ne postoji.");

            return self::FAILURE;
        }

        $this->info("=== Let #{$flight->id} pre tweak-a ===");
        $this->printState($flight, $pricing);

        if (($cap = $this->option('capacity')) !== null) {
            $this->setCapacity($flight, (int) $cap);
        }

        if (($occ = $this->option('occupancy')) !== null) {
            $this->setOccupancy($flight, (int) $occ);
        }

        if (($season = $this->option('season')) !== null) {
            $this->setSeason($flight, $season);
        }

        if ($this->option('recompute')) {
            $this->call('flights:recompute-prices', ['--force' => true]);
        }

        $flight->refresh()->load('plane', 'tickets', 'route.landingAirport');
        $this->info("=== Let #{$flight->id} posle tweak-a ===");
        $this->printState($flight, $pricing);

        return self::SUCCESS;
    }

    private function setCapacity(Flight $flight, int $capacity): void
    {
        if (! $flight->plane) {
            $this->warn('Let nema avion — preskačem capacity.');

            return;
        }
        $flight->plane->update(['capacity' => $capacity]);
        $this->line("→ Capacity aviona postavljen na {$capacity}.");
    }

    private function setOccupancy(Flight $flight, int $pct): void
    {
        $pct = max(0, min(100, $pct));
        $capacity = $flight->plane?->capacity ?? 180;
        $target = (int) round(($pct / 100) * $capacity);

        // Brojimo samo karte iz ne-otkazanih rezervacija, dok dummy karte
        // dodajemo / brišemo da pogodimo target.
        $demoTickets = FlightTicket::where('flight_id', $flight->id)
            ->whereHas('reservation', fn ($q) => $q->where('code', 'like', 'DEMO-OCC-%'))
            ->get();

        $realCount = $flight->tickets()
            ->whereHas('reservation.latestState', fn ($q) => $q->whereNotIn('status', ['cancelled']))
            ->whereDoesntHave('reservation', fn ($q) => $q->where('code', 'like', 'DEMO-OCC-%'))
            ->count();

        $needed = $target - $realCount;
        $currentDemo = $demoTickets->count();

        if ($needed === $currentDemo) {
            $this->line("→ Occupancy već na {$pct}% ({$target}/{$capacity}) — ništa za promenu.");

            return;
        }

        if ($needed < $currentDemo) {
            $toDelete = $demoTickets->take($currentDemo - $needed);
            $reservationIds = $toDelete->pluck('reservation_id')->unique();
            FlightTicket::whereIn('id', $toDelete->pluck('id'))->delete();
            Reservation::whereIn('id', $reservationIds)->delete();
            $this->line('→ Obrisao '.$toDelete->count().' demo karata.');

            return;
        }

        $toCreate = $needed - $currentDemo;
        $classId = TicketClass::first()?->id ?? 1;

        DB::transaction(function () use ($flight, $toCreate, $classId) {
            for ($i = 0; $i < $toCreate; $i++) {
                $reservation = Reservation::create([
                    'user_id' => 1,
                    'total_price' => 0,
                    'code' => 'DEMO-OCC-'.strtoupper(Str::random(6)),
                ]);
                $state = ReservationState::create([
                    'reservation_id' => $reservation->id,
                    'status' => 'confirmed',
                ]);
                $reservation->update(['latest_state_id' => $state->id]);

                FlightTicket::create([
                    'passenger_first_name' => 'Demo',
                    'passenger_last_name' => 'Occupancy',
                    'flight_id' => $flight->id,
                    'reservation_id' => $reservation->id,
                    'ticket_class_id' => $classId,
                    'base_price' => 0,
                    'final_price' => 0,
                    'seat_number' => 200 + $i,
                ]);
            }
        });

        $this->line("→ Dodao {$toCreate} demo karata. Sad ima cca {$target}/{$capacity} = {$pct}%.");
    }

    private function setSeason(Flight $flight, string $season): void
    {
        if (! in_array($season, ['summer', 'winter', 'none'], true)) {
            $this->warn("Sezona mora biti summer|winter|none, dobio: {$season}. Preskačem.");

            return;
        }
        $arr = $flight->route?->landingAirport;
        if (! $arr) {
            $this->warn('Let nema destinaciju — preskačem sezonu.');

            return;
        }
        $arr->update(['season_type' => $season]);
        $this->line("→ Destinacija {$arr->city} sad je {$season}.");
    }

    private function printState(Flight $flight, PricingService $pricing): void
    {
        $capacity = $flight->plane?->capacity ?? 0;
        $totalTickets = $flight->tickets()->count();
        $activeTickets = $flight->tickets()
            ->whereHas('reservation.latestState', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->count();
        $pct = $pricing->occupancyPct($flight);

        $this->table(
            ['Polje', 'Vrednost'],
            [
                ['Destinacija', $flight->route?->landingAirport?->city ?? '—'],
                ['Season type', $flight->route?->landingAirport?->season_type ?? '—'],
                ['Capacity', $capacity],
                ['Karata ukupno (sa otkazanima)', $totalTickets],
                ['Karata aktivnih (broji se za popunjenost)', $activeTickets],
                ['Popunjenost %', $pct],
                ['Season label', $pricing->seasonLabel($flight)],
                ['Occupancy label', $pricing->occupancyLabel($flight)],
                ['base_price (DB)', $flight->base_price ?? 'null'],
                ['current_price (DB)', $flight->current_price ?? 'null'],
                ['price_updated_at', $flight->price_updated_at ?? 'null'],
            ],
        );
    }
}
