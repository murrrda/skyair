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
    public function changeRoute(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane']);

        $routes = Route::with(['startingAirport', 'landingAirport'])
            ->where('active', true)
            ->where('id', '!=', $flight->route_id)
            ->orderBy('name')
            ->get()
            ->map(fn (Route $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'label' => ($r->startingAirport?->iata_code ?? '?').' → '.($r->landingAirport?->iata_code ?? '?')." ({$r->name})",
                'distance_km' => $r->distance_km,
                'estimated_time' => $r->estimated_time,
            ]);

        return Inertia::render('dispatcher/promena-rute', [
            'flight' => $this->serializeFlight($flight),
            'routes' => $routes,
        ]);
    }

    public function storeRouteChange(Request $request, Flight $flight): RedirectResponse
    {
        $validated = $request->validate([
            'new_route_id' => ['required', 'exists:routes,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($flight, $validated, $request) {
            RouteChange::create([
                'flight_id' => $flight->id,
                'original_route_id' => $flight->route_id,
                'new_route_id' => $validated['new_route_id'],
                'dispatcher_id' => $request->user()->id,
                'requested_at' => now(),
                'applied_at' => now(),
                'status' => 'applied',
                'reason' => $validated['reason'],
            ]);

            $flight->update(['route_id' => $validated['new_route_id']]);
        });

        return redirect()->route('dispatcher.index')
            ->with('success', 'Ruta leta uspešno promenjena.');
    }

    public function emergencyLanding(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane']);

        $airports = Airport::orderBy('iata_code')
            ->get(['id', 'iata_code', 'name', 'city', 'country'])
            ->map(fn (Airport $a) => [
                'id' => $a->id,
                'iata_code' => $a->iata_code,
                'label' => "{$a->iata_code} · {$a->city} ({$a->name})",
            ]);

        return Inertia::render('dispatcher/prinudno-sletanje', [
            'flight' => $this->serializeFlight($flight),
            'airports' => $airports,
        ]);
    }

    public function storeEmergencyLanding(Request $request, Flight $flight): RedirectResponse
    {
        $validated = $request->validate([
            'airport_id' => ['required', 'exists:airports,id'],
            'description' => ['required', 'string', 'max:2000'],
            'seriousness_level' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        DB::transaction(function () use ($flight, $validated) {
            Failure::create([
                'plane_id' => $flight->plane_id,
                'flight_id' => $flight->id,
                'report_time' => now(),
                'description' => $validated['description'],
                'seriousness_level' => $validated['seriousness_level'],
                'status' => 'waiting_for_service',
            ]);

            Plane::where('id', $flight->plane_id)->update(['status' => 'in_service']);
            $flight->update(['status' => 'emergency_landing']);
        });

        return redirect()->route('dispatcher.index')
            ->with('success', 'Nalog za prinudno sletanje je izdat. Avion je označen za servis.');
    }

    public function replacePlane(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane']);

        $availablePlanes = Plane::where('status', 'in_garage')
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
            ]);

        return Inertia::render('dispatcher/zamena-aviona', [
            'flight' => $this->serializeFlight($flight),
            'availablePlanes' => $availablePlanes,
        ]);
    }

    public function storeReplacePlane(Request $request, Flight $flight): RedirectResponse
    {
        $validated = $request->validate([
            'new_plane_id' => ['required', 'exists:planes,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($flight, $validated, $request) {
            PlaneChange::create([
                'flight_id' => $flight->id,
                'original_plane_id' => $flight->plane_id,
                'new_plane_id' => $validated['new_plane_id'],
                'dispatcher_id' => $request->user()->id,
                'requested_at' => now(),
                'applied_at' => now(),
                'status' => 'applied',
                'reason' => $validated['reason'],
            ]);

            if ($flight->plane_id) {
                Plane::where('id', $flight->plane_id)->update(['status' => 'in_service']);
            }

            $flight->update([
                'plane_id' => $validated['new_plane_id'],
                'status' => 'in_flight',
            ]);
        });

        return redirect()->route('dispatcher.index')
            ->with('success', 'Avion uspešno zamenjen. Prethodni avion je označen za servis.');
    }

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
