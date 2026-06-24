<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Failure;
use App\Models\Flight;
use App\Models\Plane;
use App\Models\PlaneChange;
use App\Models\Route;
use App\Models\RouteChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DispatcherOperationsController extends Controller
{
    // --- Route Change ---

    // Render route change form with flight data and all other active routes
    public function changeRoute(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane']);

        return Inertia::render('dispatcher/promena-rute', [
            'flight' => $this->serializeFlight($flight),
            'routes' => $this->availableRoutesExcluding($flight->route_id),
        ]);
    }

    // Validate, record RouteChange, update flight's route_id — in a transaction
    public function storeRouteChange(Request $request, Flight $flight): RedirectResponse
    {
        $validated = $this->validateRouteChange($request);

        DB::transaction(function () use ($flight, $validated, $request) {
            $this->recordRouteChange($flight, $validated, $request->user()->id);
            $flight->update(['route_id' => $validated['new_route_id']]);
        });

        return redirect()->route('dispatcher.index')
            ->with('success', 'Ruta leta uspešno promenjena.');
    }

    // --- Emergency Landing ---

    // Render emergency landing form with flight data and all airports
    public function emergencyLanding(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane']);

        return Inertia::render('dispatcher/prinudno-sletanje', [
            'flight' => $this->serializeFlight($flight),
            'airports' => $this->allAirports(),
        ]);
    }

    // Validate, if malfunction: create Failure + ground plane. Set flight to emergency_landing
    public function storeEmergencyLanding(Request $request, Flight $flight): RedirectResponse
    {
        $validated = $this->validateEmergencyLanding($request);
        $isMalfunction = $validated['reason_type'] === 'malfunction';

        DB::transaction(function () use ($flight, $validated, $isMalfunction) {
            if ($isMalfunction) {
                $this->recordFailure($flight, $validated);
                $this->groundPlane($flight->plane_id);
            }

            $flight->update(['status' => 'emergency_landing']);
        });

        return redirect()->route('dispatcher.index')
            ->with('success', $this->emergencyLandingMessage($isMalfunction));
    }

    // --- Replace Plane ---

    // Render plane replacement form with flight data and available in_garage planes
    public function replacePlane(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane']);

        return Inertia::render('dispatcher/zamena-aviona', [
            'flight' => $this->serializeFlight($flight),
            'availablePlanes' => $this->availablePlanesFor($flight),
        ]);
    }

    // Validate, record PlaneChange, ground old plane, assign new plane, resume as in_flight
    public function storeReplacePlane(Request $request, Flight $flight): RedirectResponse
    {
        $validated = $this->validatePlaneReplacement($request);

        DB::transaction(function () use ($flight, $validated, $request) {
            $this->recordPlaneChange($flight, $validated, $request->user()->id);
            $this->groundPlane($flight->plane_id);
            $flight->update([
                'plane_id' => $validated['new_plane_id'],
                'status' => 'in_flight',
            ]);
        });

        return redirect()->route('dispatcher.index')
            ->with('success', 'Avion uspešno zamenjen. Prethodni avion je označen za servis.');
    }

    // --- Validation ---

    // Validate new_route_id (must exist) + reason (string, max 1000)
    private function validateRouteChange(Request $request): array
    {
        return $request->validate([
            'new_route_id' => ['required', 'exists:routes,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
    }

    // Validate airport_id, reason_type (malfunction|weather|medical|security|other), description, seriousness 1-5
    private function validateEmergencyLanding(Request $request): array
    {
        return $request->validate([
            'airport_id' => ['required', 'exists:airports,id'],
            'reason_type' => ['required', 'string', 'in:malfunction,weather,medical,security,other'],
            'description' => ['required', 'string', 'max:2000'],
            'seriousness_level' => ['required', 'integer', 'min:1', 'max:5'],
        ]);
    }

    // Validate new_plane_id (must exist) + reason (string, max 1000)
    private function validatePlaneReplacement(Request $request): array
    {
        return $request->validate([
            'new_plane_id' => ['required', 'exists:planes,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
    }

    // --- Record Creation ---

    // Insert RouteChange with original + new route, dispatcher, timestamps, status=applied
    private function recordRouteChange(Flight $flight, array $validated, int $dispatcherId): void
    {
        RouteChange::create([
            'flight_id' => $flight->id,
            'original_route_id' => $flight->route_id,
            'new_route_id' => $validated['new_route_id'],
            'dispatcher_id' => $dispatcherId,
            'requested_at' => now(),
            'applied_at' => now(),
            'status' => 'applied',
            'reason' => $validated['reason'],
        ]);
    }

    // Insert Failure with plane_id, flight_id, description, seriousness, status=waiting_for_service
    private function recordFailure(Flight $flight, array $validated): void
    {
        Failure::create([
            'plane_id' => $flight->plane_id,
            'flight_id' => $flight->id,
            'report_time' => now(),
            'description' => $validated['description'],
            'seriousness_level' => $validated['seriousness_level'],
            'status' => 'waiting_for_service',
        ]);
    }

    // Insert PlaneChange with original + new plane, dispatcher, timestamps, status=applied
    private function recordPlaneChange(Flight $flight, array $validated, int $dispatcherId): void
    {
        PlaneChange::create([
            'flight_id' => $flight->id,
            'original_plane_id' => $flight->plane_id,
            'new_plane_id' => $validated['new_plane_id'],
            'dispatcher_id' => $dispatcherId,
            'requested_at' => now(),
            'applied_at' => now(),
            'status' => 'applied',
            'reason' => $validated['reason'],
        ]);
    }

    // --- Query Helpers ---

    // Set plane status to in_service
    private function groundPlane(?int $planeId): void
    {
        if ($planeId) {
            Plane::where('id', $planeId)->update(['status' => 'in_service']);
        }
    }

    // Get all active routes except current one, with IATA labels
    private function availableRoutesExcluding(int $currentRouteId): array
    {
        return Route::with(['startingAirport', 'landingAirport'])
            ->where('active', true)
            ->where('id', '!=', $currentRouteId)
            ->orderBy('name')
            ->get()
            ->map(fn (Route $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'label' => ($r->startingAirport?->iata_code ?? '?').' → '.($r->landingAirport?->iata_code ?? '?')." ({$r->name})",
                'distance_km' => $r->distance_km,
                'estimated_time' => $r->estimated_time,
            ])
            ->toArray();
    }

    // Get all airports sorted by IATA code, formatted as "IATA · City (Name)"
    private function allAirports(): array
    {
        return Airport::orderBy('iata_code')
            ->get(['id', 'iata_code', 'name', 'city', 'country'])
            ->map(fn (Airport $a) => [
                'id' => $a->id,
                'iata_code' => $a->iata_code,
                'label' => "{$a->iata_code} · {$a->city} ({$a->name})",
            ])
            ->toArray();
    }

    // Get in_garage planes not assigned to any active flight, excluding current plane
    private function availablePlanesFor(Flight $flight): array
    {
        return Plane::where('status', 'in_garage')
            ->when($flight->plane_id, fn ($q) => $q->where('id', '!=', $flight->plane_id))
            ->whereNotIn('id', function ($q) {
                $q->select('plane_id')
                    ->from('flights')
                    ->whereIn('status', ['scheduled', 'boarding', 'before_takeoff', 'in_flight', 'delayed']);
            })
            ->orderBy('reg_number')
            ->get()
            ->map(fn (Plane $p) => [
                'id' => $p->id,
                'reg_number' => $p->reg_number,
                'model' => $p->model,
                'capacity' => $p->capacity,
                'luxury_level' => $p->luxury_level,
            ])
            ->toArray();
    }

    // Build success message — append service note only for malfunction
    private function emergencyLandingMessage(bool $isMalfunction): string
    {
        $message = 'Nalog za prinudno sletanje je izdat.';
        if ($isMalfunction) {
            $message .= ' Avion je označen za servis.';
        }

        return $message;
    }

    // --- Serialization ---

    // Map flight + route airports + plane into a flat array for Inertia props
    private function serializeFlight(Flight $flight): array
    {
        $dep = $flight->route?->startingAirport;
        $arr = $flight->route?->landingAirport;

        return [
            'id' => $flight->id,
            'route_id' => $flight->route_id,
            'plane_id' => $flight->plane_id,
            'route_name' => $flight->route?->name ?? '—',
            'dep_code' => $dep?->iata_code ?? '—',
            'dep_city' => $dep?->city ?? '—',
            'arr_code' => $arr?->iata_code ?? '—',
            'arr_city' => $arr?->city ?? '—',
            'plane_model' => $flight->plane?->model ?? '—',
            'plane_reg' => $flight->plane?->reg_number ?? '—',
            'expected_takeoff' => $flight->expected_takeoff?->toIso8601String(),
            'expected_arrival' => $flight->expected_arrival?->toIso8601String(),
            'takeoff_formatted' => $flight->expected_takeoff?->format('d.m.Y H:i'),
            'arrival_formatted' => $flight->expected_arrival?->format('d.m.Y H:i'),
            'status' => $flight->status,
        ];
    }
}
