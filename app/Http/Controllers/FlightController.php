<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\TicketClass;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class FlightController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Flight::with(['route.startingAirport', 'route.landingAirport', 'plane', 'tickets'])
            ->whereHas('route.startingAirport')
            ->whereHas('route.landingAirport')
            ->where('expected_takeoff', '>', now());

        if ($request->filled('from')) {
            $query->whereHas('route.startingAirport', fn ($q) => $q->where('city', 'ilike', '%'.$request->from.'%')
                ->orWhere('iata_code', 'ilike', $request->from));
        }
        if ($request->filled('to')) {
            $query->whereHas('route.landingAirport', fn ($q) => $q->where('city', 'ilike', '%'.$request->to.'%')
                ->orWhere('iata_code', 'ilike', $request->to));
        }
        if ($request->filled('date')) {
            $query->whereDate('expected_takeoff', $request->date);
        }

        $sort = $request->input('sort', 'price_asc');
        $query = match ($sort) {
            'time_asc' => $query->orderBy('expected_takeoff'),
            'duration_asc' => $query->orderByRaw('expected_arrival - expected_takeoff'),
            default => $query->orderBy('expected_takeoff'),
        };

        $flights = $query->get()->map(fn (Flight $f) => $this->serializeFlight($f));

        if ($sort === 'price_asc') {
            $flights = $flights->sortBy('economy_price')->values();
        }

        return Inertia::render('kupac/rezultati-pretrage', [
            'flights' => $flights,
            'query' => $request->only(['from', 'to', 'date', 'passengers', 'class']),
            'filters' => $request->only(['price_min', 'price_max']),
            'sort' => $sort,
        ]);
    }

    public function show(Flight $flight): Response
    {
        $flight->load(['route.startingAirport', 'route.landingAirport', 'route.layovers', 'plane', 'tickets']);

        $ticketClasses = TicketClass::all()->map(function (TicketClass $tc) use ($flight) {
            $basePrice = $this->estimateBasePrice($flight);
            $multiplier = $this->classMultiplier($tc);
            $price = (int) round($basePrice * $multiplier);

            return [
                'id' => $tc->id,
                'name' => $tc->name ?? $this->fallbackClassName($tc->id),
                'price' => $price,
                'status_points' => (int) round($price * 0.03),
                'features' => $this->classFeatures($tc),
                'featured' => $multiplier === 1.5,
            ];
        });

        if ($ticketClasses->isEmpty()) {
            $ticketClasses = $this->defaultTicketClasses($flight);
        }

        $dep = $flight->route?->startingAirport;
        $arr = $flight->route?->landingAirport;
        $mins = $flight->expected_takeoff && $flight->expected_arrival
            ? $flight->expected_takeoff->diffInMinutes($flight->expected_arrival)
            : 0;
        $layoverCount = $flight->route?->layovers?->count() ?? 0;
        $occupancy = $this->occupancyPct($flight);

        return Inertia::render('kupac/detalji-leta', [
            'flight' => [
                'id' => $flight->id,
                'dep_time' => $flight->expected_takeoff?->format('H:i'),
                'arr_time' => $flight->expected_arrival?->format('H:i'),
                'dep_code' => $dep?->iata_code ?? '—',
                'dep_city' => $dep?->city ?? '—',
                'arr_code' => $arr?->iata_code ?? '—',
                'arr_city' => $arr?->city ?? '—',
                'duration' => $this->formatDuration($mins),
                'type' => $layoverCount > 0 ? "{$layoverCount} presedanje" : 'Direktan let',
                'plane_model' => $flight->plane?->model ?? '—',
                'date_formatted' => $flight->expected_takeoff?->translatedFormat('d. M Y.'),
            ],
            'ticket_classes' => $ticketClasses,
            'pricing_info' => [
                'base_price' => $this->estimateBasePrice($flight),
                'season_factor' => $this->seasonLabel(),
                'occupancy_pct' => $occupancy,
                'occupancy_factor' => $this->occupancyLabel($occupancy),
                'tier_discount' => 'Bez popusta',
            ],
        ]);
    }

    private function serializeFlight(Flight $f): array
    {
        $dep = $f->route?->startingAirport;
        $arr = $f->route?->landingAirport;
        $mins = $f->expected_takeoff && $f->expected_arrival
            ? $f->expected_takeoff->diffInMinutes($f->expected_arrival)
            : 0;
        $layoverCount = $f->route?->layovers_count ?? 0;
        $basePrice = $this->estimateBasePrice($f);
        $occupancy = $this->occupancyPct($f);

        return [
            'id' => $f->id,
            'dep_time' => $f->expected_takeoff?->format('H:i'),
            'arr_time' => $f->expected_arrival?->format('H:i'),
            'dep_code' => $dep?->iata_code ?? '—',
            'dep_city' => $dep?->city ?? '—',
            'arr_code' => $arr?->iata_code ?? '—',
            'arr_city' => $arr?->city ?? '—',
            'duration' => $this->formatDuration($mins),
            'type' => $layoverCount > 0 ? "{$layoverCount} presedanje" : 'Direktan',
            'economy_price' => (int) round($basePrice),
            'business_price' => (int) round($basePrice * 1.5),
            'first_price' => (int) round($basePrice * 2.0),
            'occupancy_pct' => $occupancy,
        ];
    }

    private function estimateBasePrice(Flight $f): float
    {
        $distance = $f->route?->distance_km ?? 1000;

        return max(5000, $distance * 10);
    }

    private function occupancyPct(Flight $f): int
    {
        $capacity = $f->plane?->capacity ?? 180;
        $sold = $f->tickets->count();

        return $capacity > 0 ? (int) round(($sold / $capacity) * 100) : 0;
    }

    private function formatDuration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return "{$h}h {$m}m";
    }

    private function seasonLabel(): string
    {
        $month = now()->month;

        return match (true) {
            $month >= 6 && $month <= 8 => 'Sezona letovanja +18%',
            $month >= 12 || $month <= 2 => 'Zimska sezona +12%',
            $month >= 3 && $month <= 5 => 'Prolećna sezona +5%',
            default => 'Jesenja sezona +3%',
        };
    }

    private function occupancyLabel(int $pct): string
    {
        return match (true) {
            $pct >= 90 => '+30% (skoro puno)',
            $pct >= 70 => '+15% (visoka potražnja)',
            $pct >= 40 => '(normalna)',
            default => '−10% (niska potražnja)',
        };
    }

    private function classMultiplier(TicketClass $tc): float
    {
        $name = strtolower($tc->name ?? '');

        return match (true) {
            str_contains($name, 'biznis') => 1.5,
            str_contains($name, 'prva') => 2.0,
            str_contains($name, 'ekonom') => 1.0,
            default => match ($tc->id % 3) {
                1 => 1.0,
                2 => 1.5,
                0 => 2.0,
            },
        };
    }

    private function fallbackClassName(int $id): string
    {
        return match ($id % 3) {
            1 => 'Ekonomska',
            2 => 'Biznis',
            0 => 'Prva',
        };
    }

    private function classFeatures(TicketClass $tc): array
    {
        $multiplier = $this->classMultiplier($tc);

        return match (true) {
            $multiplier >= 2.0 => ['Neograničen prtljag', 'Lie-flat sedište', 'À la carte meni', 'Privatni transfer'],
            $multiplier >= 1.5 => ['2 prtljaga (23kg)', 'Premium sedište', 'Topli obrok', 'Lounge pristup'],
            default => ['1 ručni prtljag', 'Standardno sedište', 'Light obrok'],
        };
    }

    private function defaultTicketClasses(Flight $f): Collection
    {
        $base = $this->estimateBasePrice($f);

        return collect([
            [
                'id' => 1,
                'name' => 'Ekonomska',
                'price' => (int) round($base),
                'status_points' => (int) round($base * 0.03),
                'features' => ['1 ručni prtljag', 'Standardno sedište', 'Light obrok'],
                'featured' => false,
            ],
            [
                'id' => 2,
                'name' => 'Biznis',
                'price' => (int) round($base * 1.5),
                'status_points' => (int) round($base * 1.5 * 0.03),
                'features' => ['2 prtljaga (23kg)', 'Premium sedište', 'Topli obrok', 'Lounge pristup'],
                'featured' => true,
            ],
            [
                'id' => 3,
                'name' => 'Prva',
                'price' => (int) round($base * 2.0),
                'status_points' => (int) round($base * 2.0 * 0.03),
                'features' => ['Neograničen prtljag', 'Lie-flat sedište', 'À la carte meni', 'Privatni transfer'],
                'featured' => false,
            ],
        ]);
    }
}
