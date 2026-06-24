<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Models\Flight;
use App\Models\Plane;
use App\Models\Route;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DispatcherFlightController extends Controller
{
    // --- CRUD ---

    // List all flights ordered by takeoff descending
    public function index(): Response
    {
        $flights = Flight::with(['route.startingAirport', 'route.landingAirport', 'plane'])
            ->orderByDesc('expected_takeoff')
            ->get()
            ->map(fn (Flight $f) => $this->serialize($f));

        return Inertia::render('dispatcher/index', ['flights' => $flights]);
    }

    // Show plane availability timeline for a date range (default: next 7 days)
    public function availability(Request $request): Response
    {
        $from = $request->input('from', now()->toDateString());
        $to = $request->input('to', now()->addDays(7)->toDateString());

        return Inertia::render('dispatcher/dostupnost-aviona', [
            'planes' => $this->planeAvailabilityData($from, $to),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    // Render flight creation form with route options
    public function create(): Response
    {
        return Inertia::render('dispatcher/zakazivanje-leta', [
            'routes' => $this->routeOptions(),
        ]);
    }

    // Validate, generate flight number, auto-suggest plane if none picked, create flight
    public function store(StoreFlightRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['dispatcher_id'] = $request->user()->id;
        $data['status'] ??= 'scheduled';
        $data['number'] = $this->generateFlightNumber();
        $data['plane_id'] = $data['plane_id'] ?: $this->findBestPlane(
            Carbon::parse($data['expected_takeoff']),
            Carbon::parse($data['expected_arrival']),
        )?->id;

        Flight::create($data);

        return redirect()->route('dispatcher.index')
            ->with('success', 'Let uspešno zakazan.');
    }

    // Load flight with all relations (route, plane, changes, failures), render detail page
    public function show(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane', 'routeChanges.originalRoute', 'routeChanges.newRoute', 'planeChanges.originalPlane', 'planeChanges.newPlane', 'failures']);

        return Inertia::render('dispatcher/detalji-leta', [
            'flight' => $this->serializeDetail($flight),
        ]);
    }

    // Render flight edit form with current data and route options
    public function edit(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'plane']);

        return Inertia::render('dispatcher/izmena-leta', [
            'flight' => $this->serialize($flight),
            'routes' => $this->routeOptions(),
        ]);
    }

    // Validate, auto-suggest plane if empty, update flight
    public function update(UpdateFlightRequest $request, Flight $flight): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['plane_id'])) {
            $data['plane_id'] = $this->findBestPlane(
                Carbon::parse($data['expected_takeoff']),
                Carbon::parse($data['expected_arrival']),
                $flight->id,
            )?->id;
        }

        $flight->update($data);

        return redirect()->route('dispatcher.index')
            ->with('success', 'Let uspešno izmenjen.');
    }

    // --- Flight Lifecycle ---

    // Guard: only scheduled/boarding/before_takeoff can start. Set status to in_flight
    public function startFlight(Flight $flight): RedirectResponse
    {
        if (! in_array($flight->status, ['scheduled', 'boarding', 'before_takeoff'])) {
            return back()->with('error', 'Let ne može biti pokrenut iz trenutnog statusa.');
        }

        $flight->update(['status' => 'in_flight']);

        return back()->with('success', 'Let je pokrenut.');
    }

    // Guard: only in_flight can end. Set landed, update plane hours/mileage, auto-service if needed
    public function endFlight(Flight $flight): RedirectResponse
    {
        if ($flight->status !== 'in_flight') {
            return back()->with('error', 'Samo letovi sa statusom "U letu" mogu biti završeni.');
        }

        $flight->load(['route', 'plane']);

        DB::transaction(function () use ($flight) {
            $flight->update(['status' => 'landed']);
            $this->updatePlaneAfterLanding($flight);
        });

        $servicedMsg = $flight->plane?->status === 'in_service'
            ? ' Avion je poslat na redovan servis.'
            : '';

        return back()->with('success', "Let je završen.{$servicedMsg}");
    }

    // --- Plane Suggestion API ---

    // Validate takeoff/arrival, rank available planes, return JSON with scores
    public function suggestPlane(Request $request): JsonResponse
    {
        $request->validate([
            'expected_takeoff' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after:expected_takeoff'],
            'exclude_flight_id' => ['nullable', 'integer'],
        ]);

        $candidates = $this->rankPlanes(
            Carbon::parse($request->expected_takeoff),
            Carbon::parse($request->expected_arrival),
            $request->integer('exclude_flight_id') ?: null,
        );

        return response()->json([
            'planes' => $candidates->map(fn (array $item) => [
                'id' => $item['plane']->id,
                'reg_number' => $item['plane']->reg_number,
                'model' => $item['plane']->model,
                'capacity' => $item['plane']->capacity,
                'luxury_level' => $item['plane']->luxury_level,
                'status' => $item['plane']->status,
                'score' => $item['score'],
                'reason' => $item['reason'],
            ])->values(),
        ]);
    }

    // --- Post-Landing Plane Update ---

    // Add flight hours + mileage to plane, check if service interval exceeded, trigger service if needed
    private function updatePlaneAfterLanding(Flight $flight): void
    {
        $plane = $flight->plane;
        if (! $plane) {
            return;
        }

        $flightHours = $this->calculateFlightHours($flight);
        $distanceKm = $flight->route?->distance_km ?? 0;

        $newTotalHours = $plane->total_flight_hours + $flightHours;
        $newHoursSinceService = $plane->hours_since_last_service + $flightHours;
        $newMileage = $plane->total_mileage + $distanceKm;

        $needsService = $plane->repair_service_interval > 0
            && $newHoursSinceService >= $plane->repair_service_interval;

        $plane->update([
            'total_flight_hours' => $newTotalHours,
            'total_mileage' => $newMileage,
            'hours_since_last_service' => $needsService ? 0 : $newHoursSinceService,
            'status' => $needsService ? 'in_service' : 'in_garage',
        ]);

        if ($needsService) {
            $this->createScheduledService($plane, $newTotalHours);
        }
    }

    // Diff takeoff/arrival in minutes, round up to whole hours
    private function calculateFlightHours(Flight $flight): int
    {
        if (! $flight->expected_takeoff || ! $flight->expected_arrival) {
            return 0;
        }

        return (int) ceil($flight->expected_takeoff->diffInMinutes($flight->expected_arrival) / 60);
    }

    // Create a pending Service record when repair_service_interval is exceeded
    private function createScheduledService(Plane $plane, int $totalHours): void
    {
        Service::create([
            'plane_id' => $plane->id,
            'admin_id' => $plane->admin_id,
            'started' => now(),
            'ended' => now(),
            'status' => 'pending',
            'description' => "Redovan servis — dostignuto {$totalHours} letnih sati (interval: {$plane->repair_service_interval}h).",
            'price' => 0,
            'service_center' => 'TBD',
        ]);
    }

    // --- Plane Ranking ---

    // Return the top-ranked plane for a time window, or null if none available
    private function findBestPlane(Carbon $takeoff, Carbon $arrival, ?int $excludeFlightId = null): ?Plane
    {
        return $this->rankPlanes($takeoff, $arrival, $excludeFlightId)->first()['plane'] ?? null;
    }

    // Score planes by capacity (-), recent failures (-), luxury (+). Exclude busy and in_service planes
    private function rankPlanes(Carbon $takeoff, Carbon $arrival, ?int $excludeFlightId = null)
    {
        $busyPlaneIds = Flight::whereIn('status', ['scheduled', 'boarding', 'before_takeoff', 'in_flight', 'delayed'])
            ->where('expected_takeoff', '<', $arrival)
            ->where('expected_arrival', '>', $takeoff)
            ->when($excludeFlightId, fn ($q) => $q->where('id', '!=', $excludeFlightId))
            ->pluck('plane_id');

        $planes = Plane::where('status', '!=', 'in_service')
            ->whereNotIn('id', $busyPlaneIds)
            ->get();

        return $planes->map(function (Plane $plane) {
            $score = 100;
            $reasons = [];

            $score -= min(30, $plane->capacity / 10);
            $reasons[] = "kapacitet: {$plane->capacity}";

            $recentFailures = $plane->failures()->where('report_time', '>', now()->subMonths(6))->count();
            $score -= $recentFailures * 10;
            if ($recentFailures > 0) {
                $reasons[] = "{$recentFailures} kvar(ova) u poslednjih 6 meseci";
            }

            $score += $plane->luxury_level * 2;
            $reasons[] = "luksuz: {$plane->luxury_level}";

            return [
                'plane' => $plane,
                'score' => round($score, 1),
                'reason' => implode(', ', $reasons),
            ];
        })->sortByDesc('score');
    }

    // --- Query Helpers ---

    // Map each plane to its flights within the date range for the availability timeline
    private function planeAvailabilityData(string $from, string $to): array
    {
        $planes = Plane::orderBy('reg_number')->get();

        $flights = Flight::whereIn('status', ['scheduled', 'boarding', 'before_takeoff', 'in_flight', 'delayed'])
            ->where('expected_takeoff', '<=', Carbon::parse($to)->endOfDay())
            ->where('expected_arrival', '>=', Carbon::parse($from)->startOfDay())
            ->get(['id', 'plane_id', 'expected_takeoff', 'expected_arrival', 'status']);

        return $planes->map(function (Plane $plane) use ($flights) {
            $planeFlights = $flights->where('plane_id', $plane->id)->values();

            return [
                'id' => $plane->id,
                'reg_number' => $plane->reg_number,
                'model' => $plane->model,
                'capacity' => $plane->capacity,
                'luxury_level' => $plane->luxury_level,
                'status' => $plane->status,
                'flights' => $planeFlights->map(fn (Flight $f) => [
                    'id' => $f->id,
                    'takeoff' => $f->expected_takeoff->toIso8601String(),
                    'arrival' => $f->expected_arrival->toIso8601String(),
                    'status' => $f->status,
                ])->toArray(),
            ];
        })->toArray();
    }

    // Get last flight number (FL-XXXXX), increment, zero-pad to 5 digits
    private function generateFlightNumber(): string
    {
        $last = Flight::orderByDesc('id')->value('number');
        $next = $last ? ((int) str_replace('FL-', '', $last)) + 1 : 1;

        return 'FL-'.str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // Get all active routes with IATA labels for select dropdowns
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
                'distance_km' => $r->distance_km,
                'estimated_time' => $r->estimated_time,
            ])
            ->toArray();
    }

    // --- Serialization ---

    // Map flight + route airports + plane into a flat array for Inertia props
    private function serialize(Flight $flight): array
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
            'ticket_count' => $flight->tickets_count ?? $flight->tickets()->count(),
        ];
    }

    // Extend serialize() with route changes, plane changes, and failures arrays
    private function serializeDetail(Flight $flight): array
    {
        $base = $this->serialize($flight);

        $base['route_changes'] = $flight->routeChanges->map(fn ($rc) => [
            'id' => $rc->id,
            'original_route' => $rc->originalRoute?->name ?? '—',
            'new_route' => $rc->newRoute?->name ?? '—',
            'reason' => $rc->reason,
            'applied_at' => $rc->applied_at?->format('d.m.Y H:i'),
        ])->toArray();

        $base['plane_changes'] = $flight->planeChanges->map(fn ($pc) => [
            'id' => $pc->id,
            'original_plane' => $pc->originalPlane?->model.' ('.$pc->originalPlane?->reg_number.')' ?? '—',
            'new_plane' => $pc->newPlane?->model.' ('.$pc->newPlane?->reg_number.')' ?? '—',
            'reason' => $pc->reason,
            'applied_at' => $pc->applied_at?->format('d.m.Y H:i'),
        ])->toArray();

        $base['failures'] = $flight->failures->map(fn ($f) => [
            'id' => $f->id,
            'description' => $f->description,
            'seriousness_level' => $f->seriousness_level,
            'status' => $f->status,
            'report_time' => $f->report_time?->format('d.m.Y H:i'),
        ])->toArray();

        return $base;
    }
}
