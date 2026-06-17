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
        return Zaposlen::query()
            ->whereIn('role', $this->eligibleRoles($roleCode))
            ->where('status', 'aktivan')
            ->whereNull('datum_otkaza')
            // Qualified: at least one valid (non-expired) certificate ...
            ->whereHas('certificates', fn ($q) => $q->where('expires_at', '>', now()))
            // ... and at least one completed training.
            ->whereHas('trainings', fn ($q) => $q->whereNotNull('finished_at'))
            // Available: not already placed on this flight, and not busy on
            // another flight overlapping this window.
            ->whereDoesntHave('assignments', function ($q) use ($flight) {
                $q->where('status', '!=', 'cancelled')
                    ->where(function ($inner) use ($flight) {
                        $inner->where('flight_id', $flight->id)
                            ->orWhereHas('flight', function ($fq) use ($flight) {
                                $fq->where('id', '!=', $flight->id)
                                    ->where('status', '!=', 'cancelled')
                                    ->where('expected_takeoff', '<', $flight->expected_arrival)
                                    ->where('expected_arrival', '>', $flight->expected_takeoff);
                            });
                    });
            })
            // Prefer candidates whose primary role matches the seat, so a
            // captain is only used as co-pilot when no co-pilot is available.
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [$roleCode])
            ->get();
    }
}
