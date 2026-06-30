<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsService
{
    /**
     * All sales-analytics aggregates for a period. The period filters flights
     * by their scheduled departure (expected_takeoff). A seat counts as "sold"
     * when its reservation's latest state is not 'cancelled' — mirroring
     * PricingService::occupancyPct(). Canonical statuses: pending / confirmed /
     * cancelled / completed.
     */
    public function analytics(Carbon $from, Carbon $to): array
    {
        $flightOccupancies = $this->flightOccupancies($from, $to);
        $cancellation = $this->cancellationStats($from, $to);

        return [
            'kpis' => $this->kpis($from, $to, $flightOccupancies, $cancellation),
            'occupancy_by_class' => $this->occupancyByClass($from, $to),
            'occupancy_by_class_by_season' => $this->occupancyByClassBySeason($from, $to),
            'cancellation' => $cancellation,
            'cancellation_trend' => $this->cancellationTrend($from, $to),
            'cancellation_by_flight' => $this->cancellationByFlight($from, $to),
            'rising_cancellations' => $this->risingCancellations($from, $to),
            'occupancy_extremes' => $this->occupancyExtremes($flightOccupancies),
        ];
    }

    /**
     * Occupancy-by-class split into calendar seasons (leto / zima / van sezone).
     * A flight's season is decided by its departure month using the very same
     * config('pricing.season') month sets that PricingService::seasonFactor()
     * reads — summer months → leto, winter months → zima, everything else →
     * van sezone — so the three buckets partition the period's flights.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function occupancyByClassBySeason(Carbon $from, Carbon $to): array
    {
        $summer = array_values(array_map('intval', (array) config('pricing.season.summer_months', [])));
        $winter = array_values(array_map('intval', (array) config('pricing.season.winter_months', [])));
        $offSeason = array_values(array_diff(range(1, 12), $summer, $winter));

        return [
            'leto' => $this->occupancyByClass($from, $to, $summer),
            'zima' => $this->occupancyByClass($from, $to, $winter),
            'van_sezone' => $this->occupancyByClass($from, $to, $offSeason),
        ];
    }

    /**
     * Per-flight occupancy for every flight departing in the period.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flightOccupancies(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                f.id     AS flight_id,
                f.number AS flight_number,
                r.name   AS route_name,
                p.capacity AS capacity,
                COUNT(ft.id) FILTER (
                    WHERE COALESCE(rs.status, 'pending') <> 'cancelled'
                ) AS sold
            FROM flights f
            JOIN planes p ON p.id = f.plane_id
            LEFT JOIN routes r ON r.id = f.route_id
            LEFT JOIN flight_tickets ft ON ft.flight_id = f.id
            LEFT JOIN reservations res ON res.id = ft.reservation_id
            LEFT JOIN reservation_states rs ON rs.id = res.latest_state_id
            WHERE f.expected_takeoff BETWEEN ? AND ?
            GROUP BY f.id, f.number, r.name, p.capacity
            ORDER BY f.expected_takeoff
            SQL,
            [$from, $to],
        );

        return array_map(function ($row) {
            $capacity = (int) $row->capacity;
            $sold = (int) $row->sold;

            return [
                'flight_id' => (int) $row->flight_id,
                'flight_number' => $row->flight_number,
                'route_name' => $row->route_name,
                'capacity' => $capacity,
                'sold' => $sold,
                'occupancy_pct' => $capacity > 0 ? min(100.0, round($sold / $capacity * 100, 2)) : 0.0,
            ];
        }, $rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $flightOccupancies
     * @param  array<string, mixed>  $cancellation
     */
    private function kpis(Carbon $from, Carbon $to, array $flightOccupancies, array $cancellation): array
    {
        $revenue = (float) DB::selectOne(
            <<<'SQL'
            SELECT COALESCE(SUM(ft.final_price), 0) AS revenue
            FROM flight_tickets ft
            JOIN flights f ON f.id = ft.flight_id
            JOIN reservations res ON res.id = ft.reservation_id
            LEFT JOIN reservation_states rs ON rs.id = res.latest_state_id
            WHERE f.expected_takeoff BETWEEN ? AND ?
              AND COALESCE(rs.status, 'pending') IN ('confirmed', 'completed')
            SQL,
            [$from, $to],
        )->revenue;

        $count = count($flightOccupancies);
        $avgOccupancy = $count > 0
            ? round(array_sum(array_column($flightOccupancies, 'occupancy_pct')) / $count, 2)
            : 0.0;

        return [
            'tickets_sold' => (int) array_sum(array_column($flightOccupancies, 'sold')),
            'revenue' => round($revenue, 2),
            'cancellation_rate_pct' => $cancellation['rate_pct'],
            'avg_occupancy_pct' => $avgOccupancy,
        ];
    }

    /**
     * Sold vs. total seats per ticket class. Total seats for a class is the
     * summed plane capacity of flights in the period multiplied by the class
     * seat share from config/pricing.php. When $months is given, only flights
     * whose departure month is in that set are counted (used to slice the
     * breakdown by calendar season).
     *
     * @param  array<int, int>|null  $months  Restrict to these departure months; null = all.
     * @return array<int, array<string, mixed>>
     */
    private function occupancyByClass(Carbon $from, Carbon $to, ?array $months = null): array
    {
        [$monthSql, $monthBindings] = $this->monthConstraint($months);

        $soldRows = DB::select(
            <<<SQL
            SELECT
                tc.id   AS class_id,
                tc.name AS class_name,
                COUNT(ft.id) FILTER (
                    WHERE f.id IS NOT NULL AND COALESCE(rs.status, 'pending') <> 'cancelled'
                ) AS sold
            FROM ticket_classes tc
            LEFT JOIN flight_tickets ft ON ft.ticket_class_id = tc.id
            LEFT JOIN flights f ON f.id = ft.flight_id AND f.expected_takeoff BETWEEN ? AND ?{$monthSql}
            LEFT JOIN reservations res ON res.id = ft.reservation_id
            LEFT JOIN reservation_states rs ON rs.id = res.latest_state_id
            GROUP BY tc.id, tc.name
            ORDER BY tc.id
            SQL,
            [$from, $to, ...$monthBindings],
        );

        $totalCapacity = (int) DB::selectOne(
            <<<SQL
            SELECT COALESCE(SUM(p.capacity), 0) AS total_capacity
            FROM flights f
            JOIN planes p ON p.id = f.plane_id
            WHERE f.expected_takeoff BETWEEN ? AND ?{$monthSql}
            SQL,
            [$from, $to, ...$monthBindings],
        )->total_capacity;

        return array_map(function ($row) use ($totalCapacity) {
            $sold = (int) $row->sold;
            $totalSeats = (int) round($totalCapacity * $this->classSeatShare($row->class_name));

            return [
                'class_id' => (int) $row->class_id,
                'class_name' => $row->class_name,
                'sold' => $sold,
                'total_seats' => $totalSeats,
                'occupancy_pct' => $totalSeats > 0 ? min(100.0, round($sold / $totalSeats * 100, 2)) : 0.0,
            ];
        }, $soldRows);
    }

    /**
     * Cancellation ratio = cancelled reservations / total reservations, over
     * reservations holding at least one ticket on a flight in the period.
     *
     * @return array<string, mixed>
     */
    private function cancellationStats(Carbon $from, Carbon $to): array
    {
        $row = DB::selectOne(
            <<<'SQL'
            SELECT
                COUNT(DISTINCT res.id) AS total,
                COUNT(DISTINCT res.id) FILTER (
                    WHERE COALESCE(rs.status, 'pending') = 'cancelled'
                ) AS cancelled
            FROM reservations res
            JOIN flight_tickets ft ON ft.reservation_id = res.id
            JOIN flights f ON f.id = ft.flight_id AND f.expected_takeoff BETWEEN ? AND ?
            LEFT JOIN reservation_states rs ON rs.id = res.latest_state_id
            SQL,
            [$from, $to],
        );

        $total = (int) $row->total;
        $cancelled = (int) $row->cancelled;

        return [
            'total_reservations' => $total,
            'cancelled_reservations' => $cancelled,
            'rate_pct' => $total > 0 ? round($cancelled / $total * 100, 2) : 0.0,
        ];
    }

    /**
     * Daily count of cancelled reservations bucketed by flight departure date,
     * zero-filled across the whole period for a clean time series.
     *
     * @return array<int, array{date: string, count: int}>
     */
    private function cancellationTrend(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT gs.d::date AS date, COALESCE(c.count, 0) AS count
            FROM generate_series(?::date, ?::date, '1 day'::interval) AS gs(d)
            LEFT JOIN (
                SELECT f.expected_takeoff::date AS day, COUNT(DISTINCT res.id) AS count
                FROM reservations res
                JOIN flight_tickets ft ON ft.reservation_id = res.id
                JOIN flights f ON f.id = ft.flight_id
                LEFT JOIN reservation_states rs ON rs.id = res.latest_state_id
                WHERE f.expected_takeoff BETWEEN ? AND ?
                  AND COALESCE(rs.status, 'pending') = 'cancelled'
                GROUP BY f.expected_takeoff::date
            ) c ON c.day = gs.d::date
            ORDER BY gs.d
            SQL,
            [$from->toDateString(), $to->toDateString(), $from, $to],
        );

        return array_map(fn ($row) => [
            'date' => $row->date,
            'count' => (int) $row->count,
        ], $rows);
    }

    /**
     * Per-flight cancellation breakdown for the period's backing table: how
     * many of a flight's reservations were cancelled, out of its total. Only
     * flights holding at least one reservation are listed, the heaviest
     * cancellations first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cancellationByFlight(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                f.id     AS flight_id,
                f.number AS flight_number,
                r.name   AS route_name,
                COUNT(DISTINCT res.id) AS total,
                COUNT(DISTINCT res.id) FILTER (
                    WHERE COALESCE(rs.status, 'pending') = 'cancelled'
                ) AS cancelled
            FROM flights f
            LEFT JOIN routes r ON r.id = f.route_id
            JOIN flight_tickets ft ON ft.flight_id = f.id
            JOIN reservations res ON res.id = ft.reservation_id
            LEFT JOIN reservation_states rs ON rs.id = res.latest_state_id
            WHERE f.expected_takeoff BETWEEN ? AND ?
            GROUP BY f.id, f.number, r.name
            HAVING COUNT(DISTINCT res.id) > 0
            ORDER BY cancelled DESC, total DESC, f.id
            LIMIT 20
            SQL,
            [$from, $to],
        );

        return array_map(function ($row) {
            $total = (int) $row->total;
            $cancelled = (int) $row->cancelled;

            return [
                'flight_id' => (int) $row->flight_id,
                'flight_number' => $row->flight_number,
                'route_name' => $row->route_name,
                'cancelled' => $cancelled,
                'total' => $total,
                'rate_pct' => $total > 0 ? round($cancelled / $total * 100, 2) : 0.0,
            ];
        }, $rows);
    }

    /**
     * Routes whose cancellations are trending upward. A flight number is unique
     * per flight (auto-generated), so the recurring unit over time is the route:
     * for each route we take its most recent flight-date cancellation counts and
     * flag the route when that short series has a positive least-squares slope.
     * A simple slope + threshold heuristic — enough to surface problem routes
     * early without over-fitting.
     *
     * @return array<int, array<string, mixed>>
     */
    private function risingCancellations(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                r.id   AS route_id,
                r.name AS route_name,
                f.expected_takeoff::date AS day,
                COUNT(DISTINCT res.id) FILTER (
                    WHERE COALESCE(rs.status, 'pending') = 'cancelled'
                ) AS cancelled
            FROM flights f
            JOIN routes r ON r.id = f.route_id
            JOIN flight_tickets ft ON ft.flight_id = f.id
            JOIN reservations res ON res.id = ft.reservation_id
            LEFT JOIN reservation_states rs ON rs.id = res.latest_state_id
            WHERE f.expected_takeoff BETWEEN ? AND ?
            GROUP BY r.id, r.name, f.expected_takeoff::date
            ORDER BY r.id, day
            SQL,
            [$from, $to],
        );

        // Chronological per-day cancellation counts, grouped by route.
        $routes = [];
        foreach ($rows as $row) {
            $id = (int) $row->route_id;
            $routes[$id] ??= ['route_id' => $id, 'route_name' => $row->route_name, 'points' => []];
            $routes[$id]['points'][] = ['date' => $row->day, 'count' => (int) $row->cancelled];
        }

        $window = 6;     // only the most recent buckets count as "recent"
        $minPoints = 3;  // need a few buckets before calling it a trend
        $minTotal = 2;   // ignore routes with a trivial number of cancellations

        $rising = [];
        foreach ($routes as $route) {
            $points = array_slice($route['points'], -$window);

            if (count($points) < $minPoints) {
                continue;
            }

            $counts = array_column($points, 'count');
            $total = array_sum($counts);

            if ($total < $minTotal) {
                continue;
            }

            $slope = $this->slope($counts);

            if ($slope <= 0) {
                continue;
            }

            $rising[] = [
                'route_id' => $route['route_id'],
                'route_name' => $route['route_name'],
                'points' => $points,
                'total_cancelled' => $total,
                'slope' => round($slope, 2),
            ];
        }

        usort($rising, fn ($a, $b) => $b['slope'] <=> $a['slope']);

        return array_slice($rising, 0, 10);
    }

    /**
     * Least-squares slope of a short integer series over indices 0..n-1.
     *
     * @param  array<int, int>  $y
     */
    private function slope(array $y): float
    {
        $n = count($y);
        if ($n < 2) {
            return 0.0;
        }

        $meanX = ($n - 1) / 2;
        $meanY = array_sum($y) / $n;

        $num = 0.0;
        $den = 0.0;
        foreach (array_values($y) as $i => $value) {
            $num += ($i - $meanX) * ($value - $meanY);
            $den += ($i - $meanX) ** 2;
        }

        return $den == 0.0 ? 0.0 : $num / $den;
    }

    /**
     * Most- and least-filled flights in the period (flights with seats only).
     *
     * @param  array<int, array<string, mixed>>  $flightOccupancies
     * @return array{highest: array<int, mixed>, lowest: array<int, mixed>}
     */
    private function occupancyExtremes(array $flightOccupancies): array
    {
        $withSeats = array_values(array_filter($flightOccupancies, fn ($f) => $f['capacity'] > 0));

        usort($withSeats, fn ($a, $b) => $b['occupancy_pct'] <=> $a['occupancy_pct']);

        $limit = 5;

        return [
            'highest' => array_slice($withSeats, 0, $limit),
            'lowest' => array_slice(array_reverse($withSeats), 0, $limit),
        ];
    }

    private function classSeatShare(string $name): float
    {
        $name = strtolower($name);

        foreach ((array) config('pricing.class_seat_share', []) as $needle => $share) {
            if ($needle !== '' && str_contains($name, (string) $needle)) {
                return (float) $share;
            }
        }

        return 0.0;
    }

    /**
     * Builds the SQL fragment + bindings that restrict flights to a set of
     * departure months. null → no restriction; an empty set → match nothing
     * (so a season with no configured months yields zero rows, not all rows).
     *
     * @param  array<int, int>|null  $months
     * @return array{0: string, 1: array<int, int>}
     */
    private function monthConstraint(?array $months): array
    {
        if ($months === null) {
            return ['', []];
        }

        $months = array_values(array_map('intval', $months));

        if ($months === []) {
            return [' AND 1 = 0', []];
        }

        $placeholders = implode(', ', array_fill(0, count($months), '?'));

        return [" AND EXTRACT(MONTH FROM f.expected_takeoff)::int IN ({$placeholders})", $months];
    }
}
