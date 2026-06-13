<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFlightTemplateRequest;
use App\Models\FlightTemplate;
use App\Models\Route;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FlightTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = FlightTemplate::with(['route.startingAirport', 'route.landingAirport'])
            ->where('dispatcher_id', auth()->id())
            ->orderBy('name')
            ->get()
            ->map(fn (FlightTemplate $t) => $this->serialize($t));

        return Inertia::render('dispatcher/sabloni', [
            'templates' => $templates,
            'routes' => $this->routeOptions(),
        ]);
    }

    public function store(StoreFlightTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['dispatcher_id'] = $request->user()->id;

        FlightTemplate::create($data);

        return redirect()->route('dispatcher.sabloni.index')
            ->with('success', 'Šablon leta uspešno sačuvan.');
    }

    public function update(StoreFlightTemplateRequest $request, FlightTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        return redirect()->route('dispatcher.sabloni.index')
            ->with('success', 'Šablon leta uspešno izmenjen.');
    }

    public function destroy(FlightTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('dispatcher.sabloni.index')
            ->with('success', 'Šablon leta uspešno obrisan.');
    }

    private function serialize(FlightTemplate $t): array
    {
        $dep = $t->route?->startingAirport;
        $arr = $t->route?->landingAirport;

        return [
            'id' => $t->id,
            'name' => $t->name,
            'route_id' => $t->route_id,
            'route_label' => ($dep?->iata_code ?? '?').' → '.($arr?->iata_code ?? '?')." ({$t->route?->name})",
            'departure_time' => substr($t->departure_time, 0, 5),
            'duration_minutes' => $t->duration_minutes,
            'min_capacity' => $t->min_capacity,
            'luxury_level' => $t->luxury_level,
            'notes' => $t->notes,
        ];
    }

    private function routeOptions(): array
    {
        return Route::with(['startingAirport', 'landingAirport'])
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Route $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'label' => ($r->startingAirport?->iata_code ?? '?').' → '.($r->landingAirport?->iata_code ?? '?')." ({$r->name})",
                'estimated_time' => $r->estimated_time,
            ])
            ->toArray();
    }
}
