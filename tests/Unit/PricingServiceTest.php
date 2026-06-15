<?php

use App\Models\Airport;
use App\Models\Flight;
use App\Models\Plane;
use App\Models\Route;
use App\Models\TicketClass;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// Boot the Laravel app (so config() works) but do NOT touch the database —
// every flight here is assembled in memory via setRelation().
uses(TestCase::class);

/**
 * Build an in-memory flight with the relations the pricing service reads.
 */
function pricingFlight(string $seasonType = 'none', int $month = 4, int $capacity = 100, int $sold = 0, ?float $base = 10000.0): Flight
{
    $airport = new Airport;
    $airport->season_type = $seasonType;

    $route = new Route;
    $route->distance_km = 500;
    $route->setRelation('landingAirport', $airport);

    $plane = new Plane;
    $plane->capacity = $capacity;

    $flight = new Flight;
    $flight->expected_takeoff = Carbon::create(2026, $month, 15, 12);
    if ($base !== null) {
        $flight->base_price = $base;
    }
    $flight->setRelation('route', $route);
    $flight->setRelation('plane', $plane);
    $flight->setRelation('tickets', collect($sold > 0 ? range(1, $sold) : []));

    return $flight;
}

beforeEach(function () {
    $this->pricing = new PricingService;
});

test('occupancy factor follows the configured tiers', function () {
    expect($this->pricing->occupancyFactor(pricingFlight(sold: 10)))->toBe(0.9)   // <40%
        ->and($this->pricing->occupancyFactor(pricingFlight(sold: 50)))->toBe(1.0)  // >=40%
        ->and($this->pricing->occupancyFactor(pricingFlight(sold: 75)))->toBe(1.15) // >=70%
        ->and($this->pricing->occupancyFactor(pricingFlight(sold: 95)))->toBe(1.3); // >=90%
});

test('season factor rewards in-season and discounts off-season', function () {
    expect($this->pricing->seasonFactor(pricingFlight('summer', 7)))->toBe(1.18) // summer dest, summer month
        ->and($this->pricing->seasonFactor(pricingFlight('summer', 1)))->toBe(0.9) // summer dest, winter month
        ->and($this->pricing->seasonFactor(pricingFlight('winter', 1)))->toBe(1.18) // winter dest, winter month
        ->and($this->pricing->seasonFactor(pricingFlight('winter', 7)))->toBe(0.9) // winter dest, summer month
        ->and($this->pricing->seasonFactor(pricingFlight('none', 7)))->toBe(1.0);  // neutral dest
});

test('current price multiplies base by occupancy and season', function () {
    // base 10000, occupancy 10% -> 0.90, summer destination in July -> 1.18
    $flight = pricingFlight('summer', 7, 100, 10, 10000);

    expect($this->pricing->computeCurrentPrice($flight))->toBe(10620.0); // 10000 * 0.90 * 1.18
});

test('class multiplier prefers the row value then falls back by name', function () {
    $business = new TicketClass;
    $business->multiplier = 1.5;

    $firstByName = new TicketClass; // no multiplier set
    $firstByName->name = 'Prva klasa';

    expect($this->pricing->classMultiplier($business))->toBe(1.5)
        ->and($this->pricing->classMultiplier($firstByName))->toBe(2.0)
        ->and($this->pricing->classMultiplier(null))->toBe(1.0);
});

test('breakdown total equals current price times the class multiplier', function () {
    // neutral season + 50% occupancy -> factors 1.0 -> current price == base 10000
    $flight = pricingFlight('none', 4, 100, 50, 10000);

    $class = new TicketClass;
    $class->name = 'Biznis';
    $class->multiplier = 1.5;

    $breakdown = $this->pricing->breakdown($flight, $class);

    expect($breakdown['base_price'])->toBe(10000)
        ->and($breakdown['total'])->toBe(15000)             // 10000 * 1.5
        ->and($breakdown['class_factor_amount'])->toBe(5000) // (1.5 - 1) * 10000
        ->and($breakdown['occupancy_amount'])->toBe(0)
        ->and($breakdown['season_amount'])->toBe(0)
        ->and($breakdown)->toHaveKeys(['reward_discount', 'status_points', 'reward_points']);
});
