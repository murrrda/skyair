<?php

namespace App\Http\Controllers;

use App\Models\Zaposlen;
use App\Services\RiskOverviewService;
use Inertia\Inertia;
use Inertia\Response;

class RiskyEmployeeController extends Controller
{
    public function __construct(private RiskOverviewService $risk) {}

    public function index(): Response
    {
        $list = $this->risk->riskyList();

        return Inertia::render('admin/incidenti/rizicni/index', [
            'employees' => $list['employees'],
            'meta' => [
                'count' => $list['employees']->count(),
                'threshold' => (int) config('incidents.analysis.threshold'),
                'window_days' => (int) config('incidents.analysis.window_days'),
                'last_analysis' => $list['last_analysis'],
            ],
        ]);
    }

    public function show(Zaposlen $zaposlen): Response
    {
        $break = $this->risk->activeBreak($zaposlen);

        abort_if($break === null, 404);

        return Inertia::render('admin/incidenti/rizicni/show', $this->risk->overview($zaposlen, $break));
    }
}
