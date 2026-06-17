<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentRequest;
use App\Models\Flight;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\SeverityLevel;
use App\Models\Zaposlen;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'incident_type_id' => $request->string('incident_type_id')->toString() ?: null,
            'severity_level_id' => $request->string('severity_level_id')->toString() ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ];

        $incidents = Incident::query()
            ->with([
                'flight.route.startingAirport',
                'flight.route.landingAirport',
                'flight.plane',
                'type:id,name',
                'severity:id,name,rank',
                'responsibleEmployees.user:id,first_name,last_name',
            ])
            ->when($filters['incident_type_id'], fn ($q, $v) => $q->where('incident_type_id', $v))
            ->when($filters['severity_level_id'], fn ($q, $v) => $q->where('severity_level_id', $v))
            ->when($filters['from'], fn ($q, $v) => $q->whereDate('occurred_at', '>=', $v))
            ->when($filters['to'], fn ($q, $v) => $q->whereDate('occurred_at', '<=', $v))
            ->when($filters['search'], function ($q, $v) {
                $q->where(function ($inner) use ($v) {
                    $inner->where('description', 'ilike', "%{$v}%")
                        ->orWhereHas('flight', fn ($f) => $f->where('number', 'ilike', "%{$v}%"));
                });
            })
            ->orderByDesc('occurred_at')
            ->paginate(7)
            ->withQueryString()
            ->through(fn (Incident $incident) => $this->serialize($incident));

        return Inertia::render('admin/incidenti/index', [
            'incidents' => $incidents,
            'incidentTypes' => IncidentType::query()->active()->orderBy('name')->get(['id', 'name']),
            'severityLevels' => SeverityLevel::query()->active()->orderBy('rank')->get(['id', 'name', 'rank', 'description']),
            'flights' => $this->flightOptions(),
            'employees' => $this->employeeOptions(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreIncidentRequest $request, IncidentService $service): RedirectResponse
    {
        $validated = $request->validated();

        $service->record(
            [
                'flight_id' => $validated['flight_id'],
                'incident_type_id' => $validated['incident_type_id'],
                'severity_level_id' => $validated['severity_level_id'],
                'occurred_at' => $validated['occurred_at'],
                'description' => $validated['description'],
            ],
            $validated['responsible_employees'] ?? [],
        );

        return redirect()->route('admin.incidenti.index')
            ->with('success', 'Incident uspešno prijavljen.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Incident $incident): array
    {
        $flight = $incident->flight;
        $dep = $flight?->route?->startingAirport?->iata_code ?? '—';
        $arr = $flight?->route?->landingAirport?->iata_code ?? '—';

        return [
            'id' => $incident->id,
            'flight' => [
                'number' => $flight?->number ?? '—',
                'route' => "{$dep}→{$arr}",
                'plane' => trim(($flight?->plane?->model ?? '').' · reg. '.($flight?->plane?->reg_number ?? '—'), ' ·'),
            ],
            'occurred_at' => $incident->occurred_at->format('d.m.Y.'),
            'occurred_time' => $incident->occurred_at->format('H:i'),
            'type' => $incident->type?->name ?? '—',
            'severity' => [
                'name' => $incident->severity?->name ?? '—',
                'rank' => $incident->severity?->rank ?? 0,
            ],
            'responsible' => $incident->responsibleEmployees->map(fn (Zaposlen $z) => [
                'user_id' => $z->user_id,
                'name' => $this->employeeName($z),
            ])->values(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function flightOptions()
    {
        return Flight::query()
            ->with(['route.startingAirport', 'route.landingAirport'])
            ->orderByDesc('expected_takeoff')
            ->get()
            ->map(function (Flight $flight) {
                $dep = $flight->route?->startingAirport?->iata_code ?? '?';
                $arr = $flight->route?->landingAirport?->iata_code ?? '?';

                return [
                    'id' => $flight->id,
                    'label' => "{$flight->number} · {$dep}→{$arr}",
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function employeeOptions()
    {
        return Zaposlen::query()
            ->where('status', '!=', 'otkazan')
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(fn (Zaposlen $z) => [
                'user_id' => $z->user_id,
                'name' => $this->employeeName($z),
            ])
            ->sortBy('name')
            ->values();
    }

    private function employeeName(Zaposlen $z): string
    {
        $name = trim(($z->user?->first_name ?? '').' '.($z->user?->last_name ?? ''));

        return $name !== '' ? $name : ('Zaposleni #'.$z->user_id);
    }
}
