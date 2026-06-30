<?php

namespace App\Http\Controllers;

use App\Services\SalesAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SalesAnalyticsController extends Controller
{
    public function __construct(private readonly SalesAnalyticsService $analytics) {}

    public function dashboard(Request $request): InertiaResponse
    {
        [$from, $to] = $this->resolvePeriod($request);

        return Inertia::render('admin/prodaja/statistike', [
            'analytics' => $this->analytics->analytics($from, $to),
            'period' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $from = Carbon::parse($data['date_from'])->startOfDay();
        $to = Carbon::parse($data['date_to'])->endOfDay();

        return response()->json($this->analytics->analytics($from, $to));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolvePeriod(Request $request): array
    {
        $rawFrom = $request->query('date_from');
        $rawTo = $request->query('date_to');

        $to = $rawTo ? Carbon::parse($rawTo)->endOfDay() : Carbon::now()->endOfDay();
        $from = $rawFrom ? Carbon::parse($rawFrom)->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
