<?php

namespace App\Services;

use App\Models\Dodeljen;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-model for crew workload. Everything is derived from existing crew
 * assignments (Dodeljen) joined to completed (landed) flights — there is no
 * separate workload table. A "leg" is one employee on one completed flight.
 */
class WorkloadService
{
    /** Display labels for the crew roles that fly. */
    public const ROLE_LABELS = [
        'pilot' => 'Pilot',
        'co_pilot' => 'Ko-pilot',
        'cabin_crew' => 'Kabinsko osoblje',
    ];

    /**
     * One row per (employee, completed flight) within [$from, $to].
     *
     * @param  array{employee_id?: int|null, role?: string|null}  $filters
     * @return Collection<int, array{user_id:int, name:string, role:string, flight_id:int, date:?string, hours:float}>
     */
    public function legs(CarbonInterface $from, CarbonInterface $to, array $filters = []): Collection
    {
        return Dodeljen::query()
            ->where('dodeljeni.status', '!=', 'cancelled')
            ->whereHas('flight', fn ($q) => $q
                ->where('status', 'landed')
                ->whereBetween('expected_takeoff', [$from, $to]))
            ->when($filters['employee_id'] ?? null, fn ($q, $id) => $q->where('zaposlen_user_id', $id))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->whereHas('zaposlen', fn ($z) => $z->where('role', $role)))
            ->with([
                'flight:id,expected_takeoff,expected_arrival',
                'zaposlen:user_id,role',
                'zaposlen.user:id,first_name,last_name',
            ])
            ->get()
            ->map(function (Dodeljen $d) {
                $flight = $d->flight;
                $hours = $flight?->expected_takeoff && $flight?->expected_arrival
                    ? $flight->expected_takeoff->diffInMinutes($flight->expected_arrival) / 60
                    : 0.0;

                $user = $d->zaposlen?->user;
                $name = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));

                return [
                    'user_id' => $d->zaposlen_user_id,
                    'name' => $name !== '' ? $name : 'Zaposleni #'.$d->zaposlen_user_id,
                    'role' => $d->zaposlen?->role ?? '—',
                    'flight_id' => $d->flight_id,
                    'date' => $flight?->expected_takeoff?->toDateString(),
                    'hours' => round($hours, 2),
                ];
            })
            ->values();
    }

    /**
     * Per-employee workload aggregated over the window.
     *
     * @param  array{employee_id?: int|null, role?: string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function perEmployee(CarbonInterface $from, CarbonInterface $to, array $filters = []): Collection
    {
        return $this->legs($from, $to, $filters)
            ->groupBy('user_id')
            ->map(function (Collection $legs) {
                $first = $legs->first();
                $flights = $legs->count();
                $hours = round($legs->sum('hours'), 1);
                $dates = $legs->pluck('date')->filter()->unique()->values()->all();

                return [
                    'user_id' => $first['user_id'],
                    'name' => $first['name'],
                    'role' => $first['role'],
                    'position' => $this->roleLabel($first['role']),
                    'flights' => $flights,
                    'hours' => $hours,
                    'avg_hours_per_flight' => $flights > 0 ? round($hours / $flights, 2) : 0.0,
                    'max_consecutive_days' => $this->maxConsecutiveDays($dates),
                ];
            })
            ->values();
    }

    public function roleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst($role);
    }

    /**
     * Longest run of consecutive calendar days among the given Y-m-d dates.
     *
     * @param  array<int, string>  $dates
     */
    public function maxConsecutiveDays(array $dates): int
    {
        if ($dates === []) {
            return 0;
        }

        $days = collect($dates)
            ->map(fn (string $d) => Carbon::parse($d)->startOfDay())
            ->unique(fn (Carbon $d) => $d->toDateString())
            ->sort()
            ->values();

        $best = 1;
        $run = 1;

        for ($i = 1; $i < $days->count(); $i++) {
            $run = $days[$i - 1]->copy()->addDay()->isSameDay($days[$i]) ? $run + 1 : 1;
            $best = max($best, $run);
        }

        return $best;
    }
}
