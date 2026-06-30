<?php

namespace App\Http\Controllers;

use App\Models\Zaposlen;
use App\Services\PerformanceReportService;
use App\Services\WorkloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceController extends Controller
{
    public function __construct(private readonly PerformanceReportService $reports) {}

    public function index(Request $request): Response
    {
        $to = ($raw = $request->string('to')->toString())
            ? Carbon::parse($raw)->endOfDay()
            : Carbon::now()->endOfDay();

        $from = ($raw = $request->string('from')->toString())
            ? Carbon::parse($raw)->startOfDay()
            : $to->copy()->subMonth()->addDay()->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $filters = [
            'employee_id' => $request->integer('employee_id') ?: null,
            'role' => $request->string('role')->toString() ?: null,
        ];

        return Inertia::render('admin/performanse/index', [
            'report' => $this->reports->report($from, $to, $filters),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'employee_id' => $filters['employee_id'],
                'role' => $filters['role'],
            ],
            'employeeOptions' => $this->employeeOptions(),
            'positionOptions' => collect(WorkloadService::ROLE_LABELS)
                ->map(fn (string $label, string $code) => ['value' => $code, 'label' => $label])
                ->values(),
        ]);
    }

    /**
     * @return Collection<int, array{value:int, label:string}>
     */
    private function employeeOptions()
    {
        return Zaposlen::query()
            ->where('status', 'aktivan')
            ->whereIn('role', array_keys(WorkloadService::ROLE_LABELS))
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(fn (Zaposlen $z) => [
                'value' => $z->user_id,
                'label' => trim(($z->user?->first_name ?? '').' '.($z->user?->last_name ?? '')) ?: 'Zaposleni #'.$z->user_id,
            ])
            ->sortBy('label')
            ->values();
    }
}
