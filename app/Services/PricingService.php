<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\TicketClass;
use Illuminate\Support\Carbon;

/**
 * Central place for all ticket pricing.
 *
 *   current_price = base_price × occupancy_factor × season_factor
 *   class_price   = current_price × class_multiplier
 *
 * The base and current prices are persisted on the flight and refreshed
 * periodically by the flights:recompute-prices command, so the price a
 * customer sees stays stable between recomputes.
 */
class PricingService
{
    /**
     * Stable base price for a flight. Uses the stored value when present,
     * otherwise derives it from the route distance.
     */
    public function basePrice(Flight $flight): float
    {
        if ($flight->base_price !== null) {
            return (float) $flight->base_price;
        }

        $distance = $flight->route?->distance_km ?? 1000;
        $min = (int) config('pricing.base.minimum', 5000);
        $perKm = (int) config('pricing.base.per_km', 10);

        return (float) max($min, $distance * $perKm);
    }

    /**
     * How full the flight is, as a percentage (0–100).
     */
    public function occupancyPct(Flight $flight): int
    {
        $capacity = $flight->plane?->capacity ?? 180;
        if ($capacity <= 0) {
            return 0;
        }

        $sold = $flight->relationLoaded('tickets')
            ? $flight->tickets->count()
            : $flight->tickets()->count();

        return (int) round(min(100, ($sold / $capacity) * 100));
    }

    /**
     * Demand factor based on occupancy.
     */
    public function occupancyFactor(Flight $flight): float
    {
        return (float) $this->occupancyTier($this->occupancyPct($flight))['factor'];
    }

    public function occupancyLabel(Flight $flight): string
    {
        return (string) $this->occupancyTier($this->occupancyPct($flight))['label'];
    }

    /**
     * Season factor based on the destination's season type and the flight month.
     * In-season destinations cost more, off-season ones cost less.
     */
    public function seasonFactor(Flight $flight): float
    {
        $cfg = config('pricing.season');
        $destinationType = $flight->route?->landingAirport?->season_type ?? 'none';
        $month = ($flight->expected_takeoff ?? Carbon::now())->month;

        $isSummer = in_array($month, $cfg['summer_months'], true);
        $isWinter = in_array($month, $cfg['winter_months'], true);

        if ($destinationType === 'summer') {
            if ($isSummer) {
                return (float) $cfg['in_season_factor'];
            }
            if ($isWinter) {
                return (float) $cfg['off_season_factor'];
            }
        }

        if ($destinationType === 'winter') {
            if ($isWinter) {
                return (float) $cfg['in_season_factor'];
            }
            if ($isSummer) {
                return (float) $cfg['off_season_factor'];
            }
        }

        return (float) $cfg['neutral_factor'];
    }

    public function seasonLabel(Flight $flight): string
    {
        $pct = (int) round(($this->seasonFactor($flight) - 1) * 100);

        return match (true) {
            $pct > 0 => "Sezonska potražnja +{$pct}%",
            $pct < 0 => "Van sezone {$pct}%",
            default => 'Neutralna sezona',
        };
    }

    /**
     * Dynamic price before the class multiplier: base × occupancy × season.
     */
    public function computeCurrentPrice(Flight $flight): float
    {
        return round(
            $this->basePrice($flight)
            * $this->occupancyFactor($flight)
            * $this->seasonFactor($flight),
            2
        );
    }

    /**
     * Current dynamic price: the persisted value when available, otherwise
     * computed on the fly (fallback before the first recompute runs).
     */
    public function currentPrice(Flight $flight): float
    {
        return $flight->current_price !== null
            ? (float) $flight->current_price
            : $this->computeCurrentPrice($flight);
    }

    /**
     * Final price a customer pays for a given class.
     */
    public function priceForClass(Flight $flight, ?TicketClass $class): float
    {
        return round($this->currentPrice($flight) * $this->classMultiplier($class), 2);
    }

    /**
     * Class price multiplier, falling back to config matched by class name.
     */
    public function classMultiplier(?TicketClass $class): float
    {
        if ($class && $class->multiplier !== null) {
            return (float) $class->multiplier;
        }

        $name = strtolower($class?->name ?? '');
        foreach ((array) config('pricing.class_multipliers', []) as $needle => $factor) {
            if ($name !== '' && str_contains($name, (string) $needle)) {
                return (float) $factor;
            }
        }

        return 1.0;
    }

    /**
     * Price breakdown for the checkout / details screens. Amounts are the
     * contribution of each factor on top of the base price; "total" is the
     * pre-reward price for the selected class.
     *
     * @return array<string, mixed>
     */
    public function breakdown(Flight $flight, ?TicketClass $class): array
    {
        $base = $this->basePrice($flight);
        $occupancyFactor = $this->occupancyFactor($flight);
        $seasonFactor = $this->seasonFactor($flight);
        $multiplier = $this->classMultiplier($class);
        $current = $this->currentPrice($flight);

        $occupancyAmount = (int) round($base * ($occupancyFactor - 1));
        $seasonAmount = (int) round($base * $occupancyFactor * ($seasonFactor - 1));
        $classAmount = (int) round($current * ($multiplier - 1));
        $classPrice = (int) round($current * $multiplier);

        return [
            'base_price' => (int) round($base),
            'class_factor_label' => $class?->name ?? '',
            'class_factor_amount' => max(0, $classAmount),
            'season_amount' => $seasonAmount,
            'occupancy_amount' => $occupancyAmount,
            'reward_discount' => 0,
            'total' => $classPrice,
            'status_points' => (int) round($classPrice * $multiplier * 0.25),
            'reward_points' => (int) round($classPrice * $multiplier),
        ];
    }

    /**
     * @return array{min:int, factor:float, label:string}
     */
    private function occupancyTier(int $pct): array
    {
        foreach ((array) config('pricing.occupancy', []) as $tier) {
            if ($pct >= $tier['min']) {
                return $tier;
            }
        }

        return ['min' => 0, 'factor' => 1.0, 'label' => ''];
    }
}
