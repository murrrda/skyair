<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use App\Models\Airport;
use App\Models\Route;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RouteController extends Controller
{
    public function index(): Response
    {
        $routes = Route::with(['startingAirport', 'landingAirport'])
            ->orderBy('name')
            ->get()
            ->map(fn (Route $r) => $this->serialize($r));

        return Inertia::render('admin/rute/index', [
            'routes' => $routes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/rute/novi', [
            'airports' => $this->airportOptions(),
        ]);
    }

    public function store(StoreRouteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['admin_id'] = $request->user()->id;

        Route::create($data);

        return redirect()->route('admin.rute.index')
            ->with('success', 'Ruta uspešno dodata.');
    }

    public function edit(Route $route): Response
    {
        return Inertia::render('admin/rute/uredi', [
            'route' => $this->serialize($route->load(['startingAirport', 'landingAirport'])),
            'airports' => $this->airportOptions(),
        ]);
    }

    public function update(UpdateRouteRequest $request, Route $route): RedirectResponse
    {
        $route->update($request->validated());

        return redirect()->route('admin.rute.index')
            ->with('success', 'Ruta uspešno izmenjena.');
    }

    public function destroy(Route $route): RedirectResponse
    {
        try {
            $route->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23503') {
                return back()->with('error', 'Ruta ne može biti obrisana jer je vezana za letove ili stajališta.');
            }
            throw $e;
        }

        return redirect()->route('admin.rute.index')
            ->with('success', 'Ruta uspešno obrisana.');
    }

    private function serialize(Route $route): array
    {
        return [
            'id' => $route->id,
            'name' => $route->name,
            'starting_airport_id' => $route->starting_airport_id,
            'landing_airport_id' => $route->landing_airport_id,
            'starting_airport' => $route->startingAirport ? [
                'id' => $route->startingAirport->id,
                'iata_code' => $route->startingAirport->iata_code,
                'city' => $route->startingAirport->city,
            ] : null,
            'landing_airport' => $route->landingAirport ? [
                'id' => $route->landingAirport->id,
                'iata_code' => $route->landingAirport->iata_code,
                'city' => $route->landingAirport->city,
            ] : null,
            'distance_km' => $route->distance_km,
            'estimated_time' => $route->estimated_time,
            'active' => $route->active,
        ];
    }

    private function airportOptions(): array
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
}
