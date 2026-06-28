<?php

namespace App\Http\Controllers;

use App\Services\SupportAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SupportAnalyticsController extends Controller
{
    public function __construct(private readonly SupportAnalyticsService $analytics) {}

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
}
