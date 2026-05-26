<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\FlightTicket;
use App\Models\LoyaltyPoint;
use App\Models\Payment;
use App\Models\Putnik;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\TicketClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $reservations = Reservation::with(['tickets.flight.route.startingAirport', 'tickets.flight.route.landingAirport', 'latestState'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Reservation $r) {
                $ticket = $r->tickets->first();
                $flight = $ticket?->flight;
                $dep = $flight?->route?->startingAirport;
                $arr = $flight?->route?->landingAirport;

                $dbStatus = $r->latestState?->status ?? 'pending';
                $status = match ($dbStatus) {
                    'pending' => 'kreirana',
                    'confirmed' => 'placena',
                    'cancelled' => 'otkazana',
                    'completed' => 'iskoriscena',
                    default => 'kreirana',
                };

                $isPast = $flight?->expected_takeoff?->isPast() ?? false;
                if ($isPast && $status === 'placena') {
                    $status = 'iskoriscena';
                }

                $ticketClass = $ticket ? TicketClass::find($ticket->ticket_class_id) : null;
                $className = $ticketClass?->name ?? ($ticket ? $this->fallbackClassName($ticket->ticket_class_id) : '');

                return [
                    'id' => $r->id,
                    'route_label' => ($dep?->city ?? '—').' → '.($arr?->city ?? '—'),
                    'info' => implode(' · ', array_filter([
                        $flight?->expected_takeoff?->translatedFormat('d. M Y.'),
                        $flight?->expected_takeoff?->format('H:i'),
                        $className,
                        $r->code,
                    ])),
                    'status' => $status,
                    'status_note' => $status === 'kreirana' ? 'Rok za plaćanje: 24h' : null,
                    'total_price' => (int) $r->total_price,
                    'is_past' => $isPast,
                    'can_pay' => $status === 'kreirana',
                    'can_cancel' => in_array($status, ['kreirana', 'placena']) && ! $isPast,
                ];
            });

        return Inertia::render('kupac/moji-letovi', [
            'reservations' => $reservations,
        ]);
    }

    public function create(Request $request): Response
    {
        $flight = Flight::with(['route.startingAirport', 'route.landingAirport', 'plane', 'tickets'])->findOrFail($request->query('flight_id'));
        $ticketClassId = (int) $request->query('ticket_class_id');

        $ticketClass = TicketClass::find($ticketClassId);
        $className = $ticketClass?->name ?? $this->fallbackClassName($ticketClassId);
        $multiplier = $ticketClass?->multiplier ?? $this->fallbackMultiplier($ticketClassId);

        $basePrice = $this->estimateBasePrice($flight);
        $classExtra = (int) round($basePrice * ($multiplier - 1));
        $total = (int) round($basePrice * $multiplier);

        $dep = $flight->route?->startingAirport;
        $arr = $flight->route?->landingAirport;

        return Inertia::render('kupac/placanje', [
            'flight' => [
                'id' => $flight->id,
                'dep_city' => $dep?->city ?? '—',
                'arr_city' => $arr?->city ?? '—',
                'dep_time' => $flight->expected_takeoff?->format('H:i'),
                'date_formatted' => $flight->expected_takeoff?->translatedFormat('d. M Y.'),
                'plane_model' => $flight->plane?->model ?? '—',
            ],
            'ticket_class_name' => $className,
            'ticket_class_id' => $ticketClassId,
            'price_breakdown' => [
                'base_price' => $basePrice,
                'class_factor_label' => $className,
                'class_factor_amount' => $classExtra,
                'season_amount' => 0,
                'occupancy_amount' => 0,
                'reward_discount' => 0,
                'total' => $total,
                'status_points' => (int) round($total * $multiplier * 0.25),
                'reward_points' => (int) round($total * $multiplier),
            ],
            'user_reward_points' => $this->getUserRewardPoints($request->user()->id),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'flight_id' => 'required|exists:flights,id',
            'ticket_class_id' => 'required|integer',
            'passenger_first_name' => 'required|string|max:255',
            'passenger_last_name' => 'required|string|max:255',
            'reward_points_used' => 'sometimes|integer|min:0',
        ]);

        $flight = Flight::with(['route', 'plane', 'tickets'])->findOrFail($request->flight_id);
        $ticketClass = TicketClass::find($request->ticket_class_id);
        $multiplier = $ticketClass?->multiplier ?? $this->fallbackMultiplier($request->ticket_class_id);

        $basePrice = $this->estimateBasePrice($flight);
        $fullPrice = round($basePrice * $multiplier, 2);

        $rewardUsed = min(
            (int) $request->input('reward_points_used', 0),
            $this->getUserRewardPoints($request->user()->id),
            (int) $fullPrice,
        );

        $finalPrice = $fullPrice - $rewardUsed;

        $capacity = $flight->plane?->capacity ?? 180;
        $takenSeats = $flight->tickets->pluck('seat_number')->toArray();
        $seat = 1;
        while (in_array($seat, $takenSeats) && $seat <= $capacity) {
            $seat++;
        }

        $reservation = DB::transaction(function () use ($request, $flight, $finalPrice, $basePrice, $seat, $rewardUsed) {
            $reservation = Reservation::create([
                'user_id' => $request->user()->id,
                'total_price' => $finalPrice,
                'code' => 'SA-'.strtoupper(Str::random(8)),
            ]);

            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'pending',
            ]);

            $reservation->update(['latest_state_id' => $state->id]);

            FlightTicket::create([
                'passenger_first_name' => $request->passenger_first_name,
                'passenger_last_name' => $request->passenger_last_name,
                'flight_id' => $flight->id,
                'reservation_id' => $reservation->id,
                'ticket_class_id' => $request->ticket_class_id,
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
                'seat_number' => $seat,
            ]);

            if ($rewardUsed > 0) {
                $putnik = Putnik::firstOrCreate(['user_id' => $request->user()->id], ['credit_card_number' => '']);
                $putnik->decrement('reward_points', $rewardUsed);

                LoyaltyPoint::create([
                    'user_id' => $request->user()->id,
                    'reservation_id' => $reservation->id,
                    'type' => 'reward',
                    'action' => 'spent',
                    'amount' => $rewardUsed,
                    'description' => 'Iskorišćeni reward poeni za '.$reservation->code,
                ]);
            }

            return $reservation;
        });

        return redirect()->route('kupac.potvrda-rezervacije', $reservation);
    }

    public function pay(Request $request, Reservation $reservation)
    {
        $reservation->load('latestState');

        if ($reservation->latestState?->status !== 'pending') {
            return redirect()->route('kupac.detalji-rezervacije', $reservation);
        }

        if ($reservation->created_at->diffInHours(now()) > 24) {
            DB::transaction(function () use ($reservation) {
                $state = ReservationState::create([
                    'reservation_id' => $reservation->id,
                    'status' => 'cancelled',
                ]);
                $reservation->update(['latest_state_id' => $state->id]);
            });

            return redirect()->route('kupac.moji-letovi')->with('error', 'Rok za plaćanje je istekao. Rezervacija je otkazana.');
        }

        $reservation->load('tickets');

        DB::transaction(function () use ($request, $reservation) {
            $payment = Payment::create([
                'amount' => $reservation->total_price,
                'method' => $request->input('payment_method', 'card'),
                'status' => 'paid',
            ]);

            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'confirmed',
            ]);

            $reservation->update([
                'payment_id' => $payment->id,
                'latest_state_id' => $state->id,
            ]);

            $ticket = $reservation->tickets->first();
            $ticketClass = $ticket ? TicketClass::find($ticket->ticket_class_id) : null;
            $multiplier = $ticketClass?->multiplier ?? ($ticket ? $this->fallbackMultiplier($ticket->ticket_class_id) : 1.0);

            $price = (float) $reservation->total_price;
            $statusEarned = (int) round($price * $multiplier * 0.25);
            $rewardEarned = (int) round($price * $multiplier);
            $expiresAt = now()->addYear();

            LoyaltyPoint::create([
                'user_id' => $reservation->user_id,
                'reservation_id' => $reservation->id,
                'type' => 'status',
                'action' => 'earned',
                'amount' => $statusEarned,
                'description' => 'Kupovina karte '.$reservation->code,
                'expires_at' => $expiresAt,
            ]);

            LoyaltyPoint::create([
                'user_id' => $reservation->user_id,
                'reservation_id' => $reservation->id,
                'type' => 'reward',
                'action' => 'earned',
                'amount' => $rewardEarned,
                'description' => 'Kupovina karte '.$reservation->code,
                'expires_at' => $expiresAt,
            ]);

            $putnik = Putnik::firstOrCreate(['user_id' => $reservation->user_id], ['credit_card_number' => '']);
            $putnik->increment('status_points', $statusEarned);
            $putnik->increment('reward_points', $rewardEarned);

            $putnik->refresh();
            $putnik->update(['tier' => $this->calculateTier($putnik->status_points)]);
        });

        return redirect()->route('kupac.potvrda-rezervacije', $reservation);
    }

    public function show(Reservation $reservation): Response
    {
        $reservation->load(['tickets.flight.route.startingAirport', 'tickets.flight.route.landingAirport', 'user']);

        $ticket = $reservation->tickets->first();
        $flight = $ticket?->flight;
        $dep = $flight?->route?->startingAirport;
        $arr = $flight?->route?->landingAirport;

        $ticketClass = $ticket ? TicketClass::find($ticket->ticket_class_id) : null;
        $className = $ticketClass?->name ?? ($ticket ? $this->fallbackClassName($ticket->ticket_class_id) : '—');
        $multiplier = $ticketClass?->multiplier ?? ($ticket ? $this->fallbackMultiplier($ticket->ticket_class_id) : 1.0);

        $price = (float) $reservation->total_price;
        $statusPoints = (int) round($price * $multiplier * 0.25);
        $rewardPoints = (int) round($price * $multiplier);

        $isPaid = $reservation->payment_id !== null;
        $putnik = Putnik::find($reservation->user_id);

        return Inertia::render('kupac/potvrda-rezervacije', [
            'reservation' => [
                'id' => $reservation->id,
                'code' => $reservation->code,
                'is_paid' => $isPaid,
                'route_label' => ($dep?->city ?? '—').' → '.($arr?->city ?? '—'),
                'date_formatted' => $flight?->expected_takeoff?->translatedFormat('d. M Y.'),
                'dep_time' => $flight?->expected_takeoff?->format('H:i'),
                'class_name' => $className,
                'seat_number' => $ticket?->seat_number ? (string) $ticket->seat_number : null,
                'total_price' => (int) $reservation->total_price,
                'status_points' => $statusPoints,
                'reward_points' => $rewardPoints,
                'total_status_points' => $putnik?->status_points ?? $statusPoints,
                'gold_progress_pct' => min(100, (int) round(($putnik?->status_points ?? 0) / 200)),
                'user_email' => $reservation->user?->email ?? '—',
            ],
        ]);
    }

    public function details(Reservation $reservation): Response
    {
        $reservation->load(['tickets.flight.route.startingAirport', 'tickets.flight.route.landingAirport', 'states', 'payment', 'user']);

        $ticket = $reservation->tickets->first();
        $flight = $ticket?->flight;
        $dep = $flight?->route?->startingAirport;
        $arr = $flight?->route?->landingAirport;

        $ticketClass = $ticket ? TicketClass::find($ticket->ticket_class_id) : null;
        $className = $ticketClass?->name ?? ($ticket ? $this->fallbackClassName($ticket->ticket_class_id) : '—');
        $multiplier = $ticketClass?->multiplier ?? ($ticket ? $this->fallbackMultiplier($ticket->ticket_class_id) : 1.0);

        $basePrice = $flight ? $this->estimateBasePrice($flight) : (int) $reservation->total_price;
        $classExtra = (int) round($basePrice * ($multiplier - 1));
        $total = (int) $reservation->total_price;
        $statusPoints = (int) round($total * 0.03);
        $rewardPoints = (int) round($total * 0.01);

        $mins = $flight?->expected_takeoff && $flight?->expected_arrival
            ? $flight->expected_takeoff->diffInMinutes($flight->expected_arrival)
            : 0;

        $dbStatus = $reservation->latestState?->status ?? 'pending';
        $status = match ($dbStatus) {
            'pending' => 'kreirana',
            'confirmed' => 'placena',
            'cancelled' => 'otkazana',
            'completed' => 'iskoriscena',
            default => 'kreirana',
        };

        $isPast = $flight?->expected_takeoff?->isPast() ?? false;
        if ($isPast && $status === 'placena') {
            $status = 'iskoriscena';
        }

        $timeline = $reservation->states->sortBy('created_at')->map(function (ReservationState $s) {
            $title = match ($s->status) {
                'pending' => 'Rezervacija kreirana',
                'confirmed' => 'Plaćanje potvrđeno',
                'cancelled' => 'Rezervacija otkazana',
                'completed' => 'Let završen',
                default => $s->status,
            };

            return [
                'title' => $title,
                'date' => $s->created_at->translatedFormat('d. M Y. H:i'),
                'state' => 'done',
            ];
        })->values()->toArray();

        if ($status === 'kreirana') {
            $timeline[] = [
                'title' => 'Čeka se plaćanje',
                'date' => 'Rok: '.$reservation->created_at->addHours(24)->translatedFormat('d. M Y. H:i'),
                'state' => 'active',
            ];
        }

        $canCancel = in_array($status, ['kreirana', 'placena']) && ! $isPast;
        $cancelDeadline = $flight?->expected_takeoff?->subDays(3)?->translatedFormat('d. M Y. H:i');

        return Inertia::render('kupac/detalji-rezervacije', [
            'reservation' => [
                'id' => $reservation->id,
                'code' => $reservation->code,
                'status' => $status,
                'date_formatted' => $flight?->expected_takeoff?->translatedFormat('d. M Y.'),
                'class_name' => $className,
                'seat_number' => $ticket?->seat_number ? (string) $ticket->seat_number : null,
                'passenger_name' => $ticket ? $ticket->passenger_first_name.' '.$ticket->passenger_last_name : '—',
                'passport_number' => null,
                'paid_at' => $reservation->payment?->created_at?->translatedFormat('d. M Y. H:i'),
                'can_cancel' => $canCancel,
                'cancel_deadline' => $cancelDeadline,
                'flight' => [
                    'dep_time' => $flight?->expected_takeoff?->format('H:i') ?? '—',
                    'arr_time' => $flight?->expected_arrival?->format('H:i') ?? '—',
                    'dep_code' => $dep?->iata_code ?? '—',
                    'dep_city' => $dep?->city ?? '—',
                    'arr_code' => $arr?->iata_code ?? '—',
                    'arr_city' => $arr?->city ?? '—',
                    'duration' => $this->formatDuration($mins),
                    'type' => 'Direktan let',
                    'plane_model' => $flight?->plane?->model ?? '—',
                ],
                'price_breakdown' => [
                    'base_price' => $basePrice,
                    'class_factor_label' => $className,
                    'class_factor_amount' => $classExtra,
                    'season_amount' => 0,
                    'occupancy_amount' => 0,
                    'reward_discount' => 0,
                    'total' => $total,
                    'status_points' => $statusPoints,
                    'reward_points' => $rewardPoints,
                ],
                'timeline' => $timeline,
            ],
        ]);
    }

    public function updateTicket(Request $request, FlightTicket $ticket)
    {
        $request->validate([
            'passenger_first_name' => 'sometimes|string|max:255',
            'passenger_last_name' => 'sometimes|string|max:255',
            'ticket_class_id' => 'sometimes|exists:ticket_classes,id',
        ]);

        $ticket->update($request->only(['passenger_first_name', 'passenger_last_name', 'ticket_class_id']));

        if ($request->filled('ticket_class_id') && $request->ticket_class_id != $ticket->getOriginal('ticket_class_id')) {
            $flight = Flight::with('route')->find($ticket->flight_id);
            $ticketClass = TicketClass::find($request->ticket_class_id);
            $multiplier = $ticketClass?->multiplier ?? $this->fallbackMultiplier($request->ticket_class_id);
            $basePrice = $this->estimateBasePrice($flight);
            $ticket->update([
                'final_price' => round($basePrice * $multiplier, 2),
            ]);

            $reservation = $ticket->reservation;
            $newTotal = $reservation->tickets()->sum('final_price');
            $reservation->update(['total_price' => $newTotal]);
        }

        return redirect()->route('kupac.detalji-rezervacije', $ticket->reservation_id);
    }

    public function destroyTicket(FlightTicket $ticket)
    {
        $reservation = $ticket->reservation;
        $ticket->delete();

        $remaining = $reservation->tickets()->count();
        if ($remaining === 0) {
            $state = ReservationState::create([
                'reservation_id' => $reservation->id,
                'status' => 'cancelled',
            ]);
            $reservation->update(['latest_state_id' => $state->id]);

            return redirect()->route('kupac.moji-letovi');
        }

        $reservation->update(['total_price' => $reservation->tickets()->sum('final_price')]);

        return redirect()->route('kupac.detalji-rezervacije', $reservation);
    }

    private function formatDuration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return "{$h}h {$m}m";
    }

    private function estimateBasePrice(Flight $f): float
    {
        $distance = $f->route?->distance_km ?? 1000;

        return max(5000, $distance * 10);
    }

    private function fallbackClassName(int $id): string
    {
        return match ($id % 3) {
            1 => 'Ekonomska',
            2 => 'Biznis',
            0 => 'Prva',
        };
    }

    private function fallbackMultiplier(int $id): float
    {
        return match ($id % 3) {
            1 => 1.0,
            2 => 1.5,
            0 => 2.0,
        };
    }

    private function getUserRewardPoints(int $userId): int
    {
        return (int) (Putnik::find($userId)?->reward_points ?? 0);
    }

    private function calculateTier(int $statusPoints): string
    {
        return match (true) {
            $statusPoints >= 20000 => 'platinum',
            $statusPoints >= 10000 => 'gold',
            default => 'silver',
        };
    }
}
