<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyPoint;
use App\Models\Putnik;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyController extends Controller
{
    private const TIER_THRESHOLDS = [
        'silver' => 0,
        'gold' => 10000,
        'platinum' => 20000,
    ];

    private const TIER_ORDER = ['silver', 'gold', 'platinum'];

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $putnik = Putnik::firstOrCreate(['user_id' => $userId], ['credit_card_number' => '']);

        $currentTier = $putnik->tier ?? 'silver';
        $currentIndex = array_search($currentTier, self::TIER_ORDER);
        $nextTier = $currentIndex < 2 ? self::TIER_ORDER[$currentIndex + 1] : null;
        $nextThreshold = $nextTier ? self::TIER_THRESHOLDS[$nextTier] : null;
        $pointsToNext = $nextThreshold ? max(0, $nextThreshold - $putnik->status_points) : 0;
        $progressPct = $nextThreshold ? min(100, (int) round(($putnik->status_points / $nextThreshold) * 100)) : 100;

        $rewardSpent = LoyaltyPoint::where('user_id', $userId)
            ->where('type', 'reward')
            ->where('action', 'spent')
            ->sum('amount');

        $expired = LoyaltyPoint::where('user_id', $userId)
            ->where('action', 'expired')
            ->sum('amount');

        return Inertia::render('kupac/loyalty', [
            'summary' => [
                'status_points' => $putnik->status_points,
                'reward_points' => $putnik->reward_points,
                'reward_spent' => (int) $rewardSpent,
                'expired' => (int) $expired,
                'tier' => $currentTier,
                'next_tier' => $nextTier,
                'points_to_next' => $pointsToNext,
                'progress_pct' => $progressPct,
            ],
            'tiers' => collect(self::TIER_ORDER)->map(fn ($t) => [
                'name' => ucfirst($t),
                'threshold' => self::TIER_THRESHOLDS[$t],
                'state' => $t === $currentTier ? 'active' : (self::TIER_THRESHOLDS[$t] < self::TIER_THRESHOLDS[$currentTier] ? 'done' : 'inactive'),
            ]),
        ]);
    }

    public function history(Request $request): Response
    {
        $userId = $request->user()->id;

        $query = LoyaltyPoint::with('reservation.tickets.flight.route.startingAirport', 'reservation.tickets.flight.route.landingAirport')
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        $points = $query->get()->map(function (LoyaltyPoint $p) {
            $ticket = $p->reservation?->tickets?->first();
            $flight = $ticket?->flight;
            $dep = $flight?->route?->startingAirport;
            $arr = $flight?->route?->landingAirport;

            return [
                'id' => $p->id,
                'route_label' => $dep && $arr ? $dep->city.' → '.$arr->city : $p->description,
                'description' => $p->description,
                'type' => $p->type,
                'action' => $p->action,
                'amount' => $p->amount,
                'date' => $p->created_at->translatedFormat('d.m.Y.'),
                'expires_at' => $p->expires_at?->translatedFormat('d.m.Y.'),
                'reservation_code' => $p->reservation?->code,
            ];
        });

        return Inertia::render('kupac/loyalty-istorija', [
            'points' => $points,
            'filters' => $request->only(['type', 'action']),
        ]);
    }
}
