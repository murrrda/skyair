<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\Plane;
use App\Models\PlaneChange;
use App\Models\RouteChange;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    /**
     * Assumed operational capacity per aircraft, used as the denominator for
     * the utilization rate (an aircraft is not expected to fly 24h a day).
     */
    private const OPERATIONAL_HOURS_PER_DAY = 12;

    public function index(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $planes = Plane::orderBy('reg_number')->get();

        $utilization = $this->fleetUtilization($planes, $from, $to);
        $flightsPerPlane = $this->flightsPerPlane($planes, $from, $to);
        $serviceIntervals = $this->averageTimeBetweenServices($planes, $from, $to);
        $changes = $this->flightPlanChangeFrequency($from, $to);

        $fleetUtilization = round((float) $utilization->avg('utilization'), 1);
        $totalFlights = (int) $flightsPerPlane->sum('flights');
        $fleetServiceInterval = $this->fleetAverage($serviceIntervals);

        return Inertia::render('admin/statistika/index', [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'fleet_utilization' => $fleetUtilization,
                'total_flights' => $totalFlights,
                'avg_days_between_services' => $fleetServiceInterval,
                'total_plan_changes' => $changes['total'],
                'planes_count' => $planes->count(),
            ],
            'utilization' => $utilization,
            'flights_per_plane' => $flightsPerPlane,
            'service_intervals' => $serviceIntervals,
            'changes' => $changes,
            'operational_hours_per_day' => self::OPERATIONAL_HOURS_PER_DAY,
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $rawTo = $request->query('to');
        $rawFrom = $request->query('from');

        $to = $rawTo ? Carbon::parse($rawTo)->endOfDay() : Carbon::now()->endOfDay();
        $from = $rawFrom ? Carbon::parse($rawFrom)->startOfDay() : $to->copy()->subMonths(6)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * Flight hours and utilization rate per aircraft, plus the fleet aggregate.
     */
    private function fleetUtilization(Collection $planes, Carbon $from, Carbon $to): Collection
    {
        $flightsByPlane = Flight::query()
            ->whereBetween('expected_takeoff', [$from, $to])
            ->get(['plane_id', 'expected_takeoff', 'expected_arrival'])
            ->groupBy('plane_id');

        $availableHours = max(1, $from->diffInDays($to) + 1) * self::OPERATIONAL_HOURS_PER_DAY;

        return $planes->map(function (Plane $plane) use ($flightsByPlane, $availableHours) {
            $flights = $flightsByPlane->get($plane->id, collect());

            $flightHours = $flights->reduce(function (float $carry, $flight) {
                $minutes = abs($flight->expected_takeoff->diffInMinutes($flight->expected_arrival));

                return $carry + $minutes / 60;
            }, 0.0);

            return [
                'plane_id' => $plane->id,
                'reg_number' => $plane->reg_number,
                'model' => $plane->model,
                'flights' => $flights->count(),
                'flight_hours' => round($flightHours, 1),
                'utilization' => round(min(100, $flightHours / $availableHours * 100), 1),
            ];
        })->values();
    }

    /**
     * Number of flights operated per aircraft within the period (zero included).
     */
    private function flightsPerPlane(Collection $planes, Carbon $from, Carbon $to): Collection
    {
        $counts = Flight::query()
            ->whereBetween('expected_takeoff', [$from, $to])
            ->selectRaw('plane_id, count(*) as total')
            ->groupBy('plane_id')
            ->pluck('total', 'plane_id');

        return $planes->map(fn (Plane $plane) => [
            'plane_id' => $plane->id,
            'reg_number' => $plane->reg_number,
            'model' => $plane->model,
            'flights' => (int) ($counts[$plane->id] ?? 0),
        ])
            ->sortByDesc('flights')
            ->values();
    }

    /**
     * Average number of days between consecutive finished services per aircraft.
     */
    private function averageTimeBetweenServices(Collection $planes, Carbon $from, Carbon $to): Collection
    {
        $servicesByPlane = Service::query()
            ->whereBetween('started', [$from, $to])
            ->orderBy('started')
            ->get(['plane_id', 'started', 'ended'])
            ->groupBy('plane_id');

        return $planes->map(function (Plane $plane) use ($servicesByPlane) {
            $services = $servicesByPlane->get($plane->id, collect())->values();

            if ($services->count() < 2) {
                return [
                    'plane_id' => $plane->id,
                    'reg_number' => $plane->reg_number,
                    'model' => $plane->model,
                    'services' => $services->count(),
                    'avg_days' => null,
                ];
            }

            $gaps = [];
            for ($i = 1; $i < $services->count(); $i++) {
                $previousEnd = $services[$i - 1]->ended ?? $services[$i - 1]->started;
                $gaps[] = abs($previousEnd->diffInDays($services[$i]->started));
            }

            return [
                'plane_id' => $plane->id,
                'reg_number' => $plane->reg_number,
                'model' => $plane->model,
                'services' => $services->count(),
                'avg_days' => round(array_sum($gaps) / count($gaps), 1),
            ];
        })->values();
    }

    /**
     * Frequency of flight-plan changes (aircraft + route) bucketed per month.
     *
     * @return array{total: int, plane_total: int, route_total: int, months: array<int, array<string, mixed>>}
     */
    private function flightPlanChangeFrequency(Carbon $from, Carbon $to): array
    {
        $months = [];
        $cursor = $from->copy()->startOfMonth();
        $lastMonth = $to->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $months[$cursor->format('Y-m')] = [
                'month' => $cursor->format('Y-m'),
                'label' => $cursor->format('m/Y'),
                'plane' => 0,
                'route' => 0,
            ];
            $cursor->addMonth();
        }

        $bucket = function ($change, string $type) use (&$months, $from, $to): void {
            $when = $change->applied_at ?? $change->requested_at;

            if ($when === null || $when->lessThan($from) || $when->greaterThan($to)) {
                return;
            }

            $key = $when->format('Y-m');
            if (isset($months[$key])) {
                $months[$key][$type]++;
            }
        };

        $planeChanges = PlaneChange::get(['requested_at', 'applied_at']);
        $routeChanges = RouteChange::get(['requested_at', 'applied_at']);

        $planeChanges->each(fn ($change) => $bucket($change, 'plane'));
        $routeChanges->each(fn ($change) => $bucket($change, 'route'));

        $months = array_values($months);
        $planeTotal = array_sum(array_column($months, 'plane'));
        $routeTotal = array_sum(array_column($months, 'route'));

        return [
            'total' => $planeTotal + $routeTotal,
            'plane_total' => $planeTotal,
            'route_total' => $routeTotal,
            'months' => $months,
        ];
    }

    private function fleetAverage(Collection $serviceIntervals): ?float
    {
        $values = $serviceIntervals
            ->pluck('avg_days')
            ->filter(fn ($value) => $value !== null);

        if ($values->isEmpty()) {
            return null;
        }

        return round((float) $values->avg(), 1);
    }
}
