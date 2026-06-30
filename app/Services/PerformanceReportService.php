<?php

namespace App\Services;

use App\Models\Zaposlen;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the crew performance report (FR #5.2) on top of WorkloadService.
 * Every metric is computed by metricsFor() so the current window and the
 * previous equal-length window run through identical code for the deltas.
 */
class PerformanceReportService
{
    public function __construct(private readonly WorkloadService $workload) {}

    /** Day-of-week labels (ISO: 1 = Monday … 7 = Sunday). */
    private const WEEKDAYS = ['Pon', 'Uto', 'Sre', 'Čet', 'Pet', 'Sub', 'Ned'];

    private const MONTHS = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'avg', 'sep', 'okt', 'nov', 'dec'];

    /**
     * @param  array{employee_id?: int|null, role?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function report(CarbonInterface $from, CarbonInterface $to, array $filters = []): array
    {
        $current = $this->metricsFor($from, $to, $filters);

        // Previous window: same length, immediately before the selected range.
        $length = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay());
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $from->copy()->subDays($length + 1)->startOfDay();
        $previous = $this->metricsFor($prevFrom, $prevTo, $filters);

        $cap = (int) config('workload.weekly_hours_cap');

        return [
            'kpis' => $current['kpis'],
            'deltas' => $this->deltas($current['kpis'], $previous['kpis']),
            'hours_by_employee' => $current['hours_by_employee'],
            'load_by_weekday' => $current['load_by_weekday'],
            'employees' => $current['employees'],
            'trends' => $this->trends($to, $filters),
            'cap' => $cap,
            'near_limit' => (int) round($cap * (float) config('workload.near_limit_ratio')),
        ];
    }

    /**
     * @param  array{employee_id?: int|null, role?: string|null}  $filters
     * @return array<string, mixed>
     */
    private function metricsFor(CarbonInterface $from, CarbonInterface $to, array $filters): array
    {
        $legs = $this->workload->legs($from, $to, $filters);
        $perEmployee = $this->workload->perEmployee($from, $to, $filters);

        $cap = (int) config('workload.weekly_hours_cap');
        $near = $cap * (float) config('workload.near_limit_ratio');

        // Peak weekly hours per employee — the cap is weekly, so status is
        // judged on the employee's busiest ISO week within the window.
        $peakWeek = $legs->groupBy('user_id')->map(
            fn (Collection $rows) => (float) $rows
                ->groupBy(fn (array $r) => Carbon::parse($r['date'])->isoFormat('GGGG-[W]WW'))
                ->map(fn (Collection $week) => $week->sum('hours'))
                ->max()
        );

        $employees = $perEmployee->map(function (array $e) use ($peakWeek, $cap, $near) {
            $peak = round($peakWeek[$e['user_id']] ?? 0, 1);
            $status = $peak > $cap ? 'over' : ($peak >= $near ? 'near' : 'normal');

            return [
                ...$e,
                'peak_week_hours' => $peak,
                'load_pct' => $cap > 0 ? (int) round($peak / $cap * 100) : 0,
                'status' => $status,
            ];
        })->sortByDesc('hours')->values();

        $totalHours = round($legs->sum('hours'), 1);
        $totalFlights = $legs->pluck('flight_id')->unique()->count();
        $activeCrew = $this->activeCrewCount($filters);

        return [
            'kpis' => [
                'total_flights' => $totalFlights,
                'total_hours' => $totalHours,
                'avg_hours_per_employee' => $activeCrew > 0 ? round($totalHours / $activeCrew, 1) : 0.0,
                'over_limit' => $employees->where('status', 'over')->count(),
                'avg_consecutive_days' => $employees->isNotEmpty() ? round($employees->avg('max_consecutive_days'), 1) : 0.0,
                'active_employees' => $activeCrew,
            ],
            'employees' => $employees,
            'hours_by_employee' => $employees->take(8)->map(fn (array $e) => [
                'name' => $this->shortName($e['name']),
                'hours' => $e['hours'],
                'status' => $e['status'],
            ])->values(),
            'load_by_weekday' => $this->loadByWeekday($legs),
        ];
    }

    /**
     * Distinct-flight counts per weekday (weekend flagged).
     *
     * @param  Collection<int, array<string, mixed>>  $legs
     * @return array<int, array{day: string, flights: int, weekend: bool}>
     */
    private function loadByWeekday(Collection $legs): array
    {
        $byDow = $legs->unique('flight_id')
            ->groupBy(fn (array $r) => Carbon::parse($r['date'])->dayOfWeekIso)
            ->map->count();

        return collect(range(1, 7))->map(fn (int $dow) => [
            'day' => self::WEEKDAYS[$dow - 1],
            'flights' => (int) ($byDow[$dow] ?? 0),
            'weekend' => $dow >= 6,
        ])->all();
    }

    /**
     * Monthly series (hours, flights, avg consecutive days) for the six months
     * ending at $to — feeds the three trend sparklines.
     *
     * @param  array{employee_id?: int|null, role?: string|null}  $filters
     * @return array<string, mixed>
     */
    private function trends(CarbonInterface $to, array $filters): array
    {
        $points = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $to->copy()->startOfMonth()->subMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $legs = $this->workload->legs($monthStart, $monthEnd, $filters);
            $perEmployee = $this->workload->perEmployee($monthStart, $monthEnd, $filters);

            $points[] = [
                'label' => self::MONTHS[(int) $monthStart->format('n') - 1],
                'hours' => round($legs->sum('hours'), 1),
                'flights' => $legs->pluck('flight_id')->unique()->count(),
                'consecutive_days' => $perEmployee->isNotEmpty() ? round($perEmployee->avg('max_consecutive_days'), 1) : 0.0,
            ];
        }

        return ['points' => $points];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, array{abs: float, pct: int|null}>
     */
    private function deltas(array $current, array $previous): array
    {
        $deltas = [];

        foreach (['total_flights', 'total_hours', 'avg_hours_per_employee', 'over_limit', 'avg_consecutive_days'] as $key) {
            $now = (float) $current[$key];
            $prev = (float) $previous[$key];
            $deltas[$key] = [
                'abs' => round($now - $prev, 1),
                'pct' => $prev != 0.0 ? (int) round(($now - $prev) / $prev * 100) : null,
            ];
        }

        return $deltas;
    }

    /**
     * @param  array{employee_id?: int|null, role?: string|null}  $filters
     */
    private function activeCrewCount(array $filters): int
    {
        return Zaposlen::query()
            ->where('status', 'aktivan')
            ->whereIn('role', array_keys(WorkloadService::ROLE_LABELS))
            ->when($filters['employee_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->count();
    }

    private function shortName(string $name): string
    {
        $parts = explode(' ', trim($name));

        if (count($parts) < 2) {
            return $name;
        }

        return mb_substr($parts[0], 0, 1).'. '.end($parts);
    }
}
