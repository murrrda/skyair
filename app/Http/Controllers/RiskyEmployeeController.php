<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\PeriodRizika;
use App\Models\Zaposlen;
use App\Services\IncidentAnalysisService;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class RiskyEmployeeController extends Controller
{
    private const ROLE_LABELS = [
        'admin' => 'Admin',
        'pilot' => 'Pilot',
        'dispatcher' => 'Dispečer',
        'agent' => 'Agent',
        'cabin_crew' => 'Kabinsko osoblje',
    ];

    public function index(): Response
    {
        $windowDays = (int) config('incidents.analysis.window_days');
        $threshold = (int) config('incidents.analysis.threshold');

        $periods = PeriodRizika::query()
            ->with(['zaposlen.user:id,first_name,last_name,email', 'razlog'])
            ->whereHas('razlog', fn (Builder $q) => $q->where('naziv', IncidentAnalysisService::REASON))
            ->where($this->activeWindow())
            ->orderByDesc('datum_pocetka')
            ->get()
            ->unique('zaposlen_id')
            ->values();

        $employees = $periods->map(function (PeriodRizika $period) use ($windowDays) {
            $zaposlen = $period->zaposlen;

            return [
                'user_id' => $zaposlen?->user_id,
                'name' => $this->employeeName($zaposlen),
                'initials' => $this->initials($zaposlen),
                'role' => $this->roleLabel($zaposlen?->role),
                'incident_count' => $this->incidentCount($zaposlen?->user_id, $windowDays),
                'pause_from' => $period->datum_pocetka?->format('d.m.Y.'),
                'pause_to' => $period->datum_kraja?->format('d.m.Y.'),
            ];
        });

        $lastAnalysis = $periods->max(fn (PeriodRizika $p) => $p->created_at);

        return Inertia::render('admin/incidenti/rizicni/index', [
            'employees' => $employees,
            'meta' => [
                'count' => $employees->count(),
                'threshold' => $threshold,
                'window_days' => $windowDays,
                'last_analysis' => $lastAnalysis?->format('d.m.Y. H:i'),
            ],
        ]);
    }

    public function show(Zaposlen $zaposlen): Response
    {
        $windowDays = (int) config('incidents.analysis.window_days');
        $threshold = (int) config('incidents.analysis.threshold');
        $pauseDays = (int) config('incidents.analysis.pause_days');

        $period = PeriodRizika::query()
            ->with('razlog')
            ->where('zaposlen_id', $zaposlen->user_id)
            ->whereHas('razlog', fn (Builder $q) => $q->where('naziv', IncidentAnalysisService::REASON))
            ->where($this->activeWindow())
            ->orderByDesc('datum_pocetka')
            ->firstOrFail();

        $zaposlen->load('user:id,first_name,last_name,email');

        $windowStart = now()->subDays($windowDays);
        $recentCount = $this->incidentCount($zaposlen->user_id, $windowDays);
        $totalCount = Incident::query()
            ->whereHas('responsibleEmployees', fn (Builder $q) => $q->whereKey($zaposlen->user_id))
            ->count();

        $incidents = Incident::query()
            ->with([
                'flight.route.startingAirport',
                'flight.route.landingAirport',
                'flight.plane',
                'type:id,name',
                'severity:id,name,rank',
                'responsibleEmployees.user:id,first_name,last_name',
            ])
            ->whereHas('responsibleEmployees', fn (Builder $q) => $q->whereKey($zaposlen->user_id))
            ->where('occurred_at', '>=', $windowStart)
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (Incident $incident) => $this->serializeIncident($incident, $zaposlen->user_id));

        $pauseFrom = $period->datum_pocetka;
        $pauseTo = $period->datum_kraja;

        return Inertia::render('admin/incidenti/rizicni/show', [
            'employee' => [
                'user_id' => $zaposlen->user_id,
                'name' => $this->employeeName($zaposlen),
                'initials' => $this->initials($zaposlen),
                'role' => $this->roleLabel($zaposlen->role),
                'email' => $zaposlen->user?->email,
            ],
            'pause' => [
                'from' => $pauseFrom?->format('d.m.Y.'),
                'to' => $pauseTo?->format('d.m.Y.'),
                'duration_days' => ($pauseFrom && $pauseTo) ? (int) $pauseFrom->diffInDays($pauseTo) : $pauseDays,
                'reason' => $this->reasonText($recentCount, $threshold, $windowDays, $pauseDays),
            ],
            'stats' => [
                'recent_count' => $recentCount,
                'threshold' => $threshold,
                'over_by' => max(0, $recentCount - $threshold),
                'window_days' => $windowDays,
                'total_count' => $totalCount,
            ],
            'incidents' => $incidents,
        ]);
    }

    /**
     * Closure constraining a query to risk periods that are still active
     * (open-ended or ending in the future).
     */
    private function activeWindow(): \Closure
    {
        return fn (Builder $q) => $q->whereNull('datum_kraja')->orWhere('datum_kraja', '>', now());
    }

    private function incidentCount(?int $userId, int $windowDays): int
    {
        if ($userId === null) {
            return 0;
        }

        return Incident::query()
            ->whereHas('responsibleEmployees', fn (Builder $q) => $q->whereKey($userId))
            ->where('occurred_at', '>=', now()->subDays($windowDays))
            ->count();
    }

    private function reasonText(int $count, int $threshold, int $windowDays, int $pauseDays): string
    {
        return "Zaposleni je u poslednjih {$windowDays} dana učestvovao u {$count} incidenata, ".
            "čime je prekoračen dozvoljeni maksimum od {$threshold}. ".
            "Sistem je automatski pokrenuo obaveznu pauzu u trajanju od {$pauseDays} dana.";
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeIncident(Incident $incident, int $selfUserId): array
    {
        $flight = $incident->flight;
        $dep = $flight?->route?->startingAirport?->iata_code ?? '—';
        $arr = $flight?->route?->landingAirport?->iata_code ?? '—';

        $others = $incident->responsibleEmployees
            ->filter(fn (Zaposlen $z) => $z->user_id !== $selfUserId)
            ->map(fn (Zaposlen $z) => $this->employeeName($z))
            ->values();

        return [
            'id' => $incident->id,
            'flight' => [
                'number' => $flight?->number ?? '—',
                'route' => "{$dep}→{$arr}",
                'plane' => $flight?->plane?->model ?? '—',
            ],
            'occurred_at' => $incident->occurred_at->format('d.m.Y.'),
            'occurred_time' => $incident->occurred_at->format('H:i'),
            'type' => $incident->type?->name ?? '—',
            'severity' => [
                'name' => $incident->severity?->name ?? '—',
                'rank' => $incident->severity?->rank ?? 0,
            ],
            'others' => $others,
        ];
    }

    private function roleLabel(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ($role ?? '—');
    }

    private function employeeName(?Zaposlen $z): string
    {
        if ($z === null) {
            return 'Zaposleni';
        }

        $name = trim(($z->user?->first_name ?? '').' '.($z->user?->last_name ?? ''));

        return $name !== '' ? $name : ('Zaposleni #'.$z->user_id);
    }

    private function initials(?Zaposlen $z): string
    {
        $first = $z?->user?->first_name ?? '';
        $last = $z?->user?->last_name ?? '';
        $initials = mb_strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
