<?php

namespace App\Http\Controllers;

use App\Services\SupportAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SupportAnalyticsController extends Controller
{
    public function __construct(private readonly SupportAnalyticsService $analytics) {}

    public function dashboard(Request $request): InertiaResponse
    {
        [$from, $to] = $this->resolvePeriod($request);

        return Inertia::render('admin/podrska/statistike', [
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

    public function downloadPdf(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $analytics = $this->analytics->analytics($from, $to);

        $trend = $this->bucketDailyCounts($analytics['daily_counts']);

        $pdf = Pdf::loadView('pdf.support-analytics', [
            'analytics' => $analytics,
            'period' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'date_from_human' => $from->format('d.m.Y.'),
                'date_to_human' => $to->format('d.m.Y.'),
            ],
            'trend' => $trend,
            'generated_at' => Carbon::now()->format('d.m.Y. H:i'),
        ])->setPaper('a4');

        $filename = 'podrska-izvestaj-'.$to->toDateString().'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  array<int, array{date: string, count: int}>  $dailyCounts
     * @return array{granularity: string, granularity_label: string, rows: array<int, array{label: string, count: int}>}
     */
    private function bucketDailyCounts(array $dailyCounts): array
    {
        $days = count($dailyCounts);

        if ($days <= 14) {
            $granularity = 'daily';
            $granularityLabel = 'Dnevno';
            $rows = array_map(static fn (array $r) => [
                'label' => Carbon::parse($r['date'])->format('d.m.Y.'),
                'count' => (int) $r['count'],
            ], $dailyCounts);
        } elseif ($days <= 90) {
            $granularity = 'weekly';
            $granularityLabel = 'Nedeljno';
            $buckets = [];

            foreach ($dailyCounts as $row) {
                $d = Carbon::parse($row['date']);
                $key = $d->isoFormat('GGGG-[W]WW');
                if (! isset($buckets[$key])) {
                    $buckets[$key] = [
                        'label' => 'Nedelja '.$d->isoWeek().' ('.$d->copy()->startOfWeek()->format('d.m.').' – '.$d->copy()->endOfWeek()->format('d.m.Y.').')',
                        'count' => 0,
                    ];
                }
                $buckets[$key]['count'] += (int) $row['count'];
            }
            $rows = array_values($buckets);
        } else {
            $granularity = 'monthly';
            $granularityLabel = 'Mesečno';
            $months = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'avg', 'sep', 'okt', 'nov', 'dec'];
            $buckets = [];

            foreach ($dailyCounts as $row) {
                $d = Carbon::parse($row['date']);
                $key = $d->format('Y-m');
                if (! isset($buckets[$key])) {
                    $buckets[$key] = [
                        'label' => $months[(int) $d->format('n') - 1].' '.$d->format('Y'),
                        'count' => 0,
                    ];
                }
                $buckets[$key]['count'] += (int) $row['count'];
            }
            $rows = array_values($buckets);
        }

        return [
            'granularity' => $granularity,
            'granularity_label' => $granularityLabel,
            'rows' => $rows,
        ];
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
