<?php

namespace App\Services;

use App\Models\Dodeljen;
use App\Models\Flight;
use App\Models\Uloga;
use App\Models\Zaposlen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CrewAssignmentService
{
    /**
     * Minimum crew composition per flight, keyed by role code.
     * 1 pilot (captain), 1 co-pilot, and the required cabin crew.
     *
     * @var array<string, int>
     */
    public const REQUIRED_CREW = [
        'pilot' => 1,
        'co_pilot' => 1,
        'cabin_crew' => 2,
    ];

    /**
     * Minimum rest, in hours, that must separate two flights for the same
     * employee. Enforced symmetrically around the new flight's window.
     */
    public const MIN_REST_HOURS = 2;

    /**
     * Maximum scheduled flight hours an employee may accumulate within any
     * rolling 7-day window (±7 days around the new flight's takeoff).
     */
    public const MAX_WEEKLY_FLIGHT_HOURS = 60;

    /**
     * Automatically assign qualified, available crew to a flight.
     *
     * The flight's crew_status is set to 'staffed' when the full minimum
     * composition is met, otherwise 'understaffed' (no unqualified crew is
     * ever assigned).
     */
    public function assign(Flight $flight): void
    {
        DB::transaction(function () use ($flight) {
            $uloge = Uloga::pluck('id', 'code');
            $fullyStaffed = true;

            foreach (self::REQUIRED_CREW as $code => $needed) {
                $ulogaId = $uloge[$code] ?? null;
                if ($ulogaId === null) {
                    $fullyStaffed = false;

                    continue;
                }

                $candidates = $this->eligibleEmployees($flight, $code)->take($needed);

                foreach ($candidates as $zaposlen) {
                    Dodeljen::create([
                        'flight_id' => $flight->id,
                        'zaposlen_user_id' => $zaposlen->user_id,
                        'uloga_id' => $ulogaId,
                        'status' => 'confirmed',
                    ]);
                }

                if ($candidates->count() < $needed) {
                    $fullyStaffed = false;
                }
            }

            $flight->update([
                'crew_status' => $fullyStaffed ? 'staffed' : 'understaffed',
            ]);
        });
    }

    /**
     * Roles allowed to fill a given crew seat. A captain (pilot) may also
     * serve as co-pilot, but not the other way around.
     *
     * @return array<int, string>
     */
    private function eligibleRoles(string $roleCode): array
    {
        return match ($roleCode) {
            'co_pilot' => ['co_pilot', 'pilot'],
            default => [$roleCode],
        };
    }

    /**
     * Active employees who may fill the given seat, are qualified, and are
     * available for the flight's time window.
     *
     * @return Collection<int, Zaposlen>
     */
    public function eligibleEmployees(Flight $flight, string $roleCode): Collection
    {
        // Pad the flight's window by the required rest on both sides, so a
        // candidate is rejected unless there is at least MIN_REST_HOURS of
        // clearance before takeoff and after arrival.
        $restStart = $flight->expected_takeoff->copy()->subHours(self::MIN_REST_HOURS);
        $restEnd = $flight->expected_arrival->copy()->addHours(self::MIN_REST_HOURS);

        $candidates = Zaposlen::query()
            ->whereIn('role', $this->eligibleRoles($roleCode))
            ->where('status', 'aktivan')
            ->whereNull('datum_otkaza')
            // Qualified: at least one valid (non-expired) certificate ...
            ->whereHas('certificates', fn ($q) => $q->where('expires_at', '>', now()))
            // ... and at least one completed training.
            ->whereHas('trainings', fn ($q) => $q->whereNotNull('finished_at'))
            // Available: not already placed on this flight, and not busy on
            // another flight whose window (plus the rest buffer) overlaps.
            ->whereDoesntHave('assignments', function ($q) use ($flight, $restStart, $restEnd) {
                $q->where('status', '!=', 'cancelled')
                    ->where(function ($inner) use ($flight, $restStart, $restEnd) {
                        $inner->where('flight_id', $flight->id)
                            ->orWhereHas('flight', function ($fq) use ($flight, $restStart, $restEnd) {
                                $fq->where('id', '!=', $flight->id)
                                    ->where('status', '!=', 'cancelled')
                                    ->where('expected_takeoff', '<', $restEnd)
                                    ->where('expected_arrival', '>', $restStart);
                            });
                    });
            })
            // Load each candidate's assignments so we can weigh both the weekly
            // cap and their total accumulated flight hours.
            ->with(['assignments' => fn ($q) => $q->where('status', '!=', 'cancelled')->with('flight')])
            ->get();

        // Reject anyone who would exceed the weekly flight-hour cap once this
        // flight is added — a check that requires summation, so it runs here
        // rather than in SQL.
        $newDuration = $this->flightHours($flight);
        $windowStart = $flight->expected_takeoff->copy()->subDays(7);
        $windowEnd = $flight->expected_takeoff->copy()->addDays(7);

        return $candidates
            ->filter(function (Zaposlen $zaposlen) use ($flight, $newDuration, $windowStart, $windowEnd) {
                $scheduled = $zaposlen->assignments
                    ->pluck('flight')
                    ->filter(fn (?Flight $f) => $f
                        && $f->id !== $flight->id
                        && $f->status !== 'cancelled'
                        && $f->expected_takeoff?->between($windowStart, $windowEnd))
                    ->sum(fn (Flight $f) => $this->flightHours($f));

                return ($scheduled + $newDuration) <= self::MAX_WEEKLY_FLIGHT_HOURS;
            })
            // Distribute load evenly: among the fit candidates, take those with
            // the fewest accumulated flight hours first. Role match stays the
            // primary key (a captain fills a co-pilot seat only as a fallback),
            // with total flight hours as the tie-breaker within each group.
            ->sortBy(fn (Zaposlen $zaposlen) => $this->accumulatedFlightHours($zaposlen, $flight))
            ->sortBy(fn (Zaposlen $zaposlen) => $zaposlen->role === $roleCode ? 0 : 1)
            ->values();
    }

    /**
     * Scheduled flight duration in hours (expected takeoff → arrival).
     */
    private function flightHours(Flight $flight): float
    {
        return $flight->expected_takeoff->diffInMinutes($flight->expected_arrival) / 60;
    }

    /**
     * Total flight hours an employee has already accumulated across their
     * (non-cancelled) assignments, used to favour the least-loaded crew.
     */
    private function accumulatedFlightHours(Zaposlen $zaposlen, Flight $excluding): float
    {
        return $zaposlen->assignments
            ->pluck('flight')
            ->filter(fn (?Flight $f) => $f && $f->id !== $excluding->id && $f->status !== 'cancelled')
            ->sum(fn (Flight $f) => $this->flightHours($f));
    }
}
