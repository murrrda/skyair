<?php

namespace App\Console\Commands;

use App\Models\Flight;
use App\Services\PricingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('flights:recompute-prices {--force : Recompute even flights updated within the interval}')]
#[Description('Periodično preračunava dinamičke cene budućih letova (popunjenost + sezona)')]
class RecomputeFlightPrices extends Command
{
    public function handle(PricingService $pricing): int
    {
        $intervalDays = (int) config('pricing.recompute_interval_days', 3);
        $threshold = now()->subDays($intervalDays);
        $force = (bool) $this->option('force');

        $flights = Flight::query()
            ->with(['route.landingAirport', 'plane', 'tickets'])
            ->where('expected_takeoff', '>', now())
            ->when(! $force, fn ($q) => $q->where(
                fn ($q) => $q->whereNull('price_updated_at')->orWhere('price_updated_at', '<=', $threshold)
            ))
            ->get();

        $updated = 0;
        foreach ($flights as $flight) {
            // basePrice() derives from the route distance when missing; we
            // persist it so it stays stable across future recomputes.
            $flight->update([
                'base_price' => $pricing->basePrice($flight),
                'current_price' => $pricing->computeCurrentPrice($flight),
                'price_updated_at' => now(),
            ]);

            $updated++;
        }

        $this->info("Ažurirane cene za {$updated} let(ova) (interval: {$intervalDays} dana).");

        return self::SUCCESS;
    }
}
