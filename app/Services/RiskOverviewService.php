<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\PeriodRizika;
use App\Models\Zaposlen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reads the incident-driven risk state and shapes it for both the admin
 * review pages and the employee self-service page, so the two always agree.
 */
class RiskOverviewService
{
    private const ROLE_LABELS = [
        'admin' => 'Admin',
        'pilot' => 'Pilot',
        'dispatcher' => 'Dispečer',
        'agent' => 'Agent',
        'cabin_crew' => 'Kabinsko osoblje',
    ];

    /**
     * The employee's currently active incident-driven pause, if any.
     */
    public function activeBreak(Zaposlen $employee): ?PeriodRizika
    {
        return $employee->periodiRizika()
            ->with('razlog')
            ->whereHas('razlog', fn (Builder $q) => $q->where('naziv', IncidentAnalysisService::REASON))
            ->where(fn (Builder $q) => $q->whereNull('datum_kraja')->orWhere('datum_kraja', '>', now()))
            ->orderByDesc('datum_pocetka')
            ->first();
    }

    /**
     * Card-level summary of every employee currently on an incident-driven pause.
     *
     * @return array{employees: Collection<int, array<string, mixed>>, last_analysis: ?string}
     */
    public function riskyList(): array
    {
        $windowDays = (int) config('incidents.analysis.window_days');

        $periods = PeriodRizika::query()
            ->with(['zaposlen.user:id,first_name,last_name,email', 'razlog'])
            ->whereHas('razlog', fn (Builder $q) => $q->where('naziv', IncidentAnalysisService::REASON))
            ->where(fn (Builder $q) => $q->whereNull('datum_kraja')->orWhere('datum_kraja', '>', now()))
            ->orderByDesc('datum_pocetka')
            ->get()
            ->unique('zaposlen_id')
            ->values();

        $employees = $periods->map(fn (PeriodRizika $period) => [
            'user_id' => $period->zaposlen?->user_id,
            'name' => $this->employeeName($period->zaposlen),
            'initials' => $this->initials($period->zaposlen),
            'role' => $this->roleLabel($period->zaposlen?->role),
            'incident_count' => $this->incidentCount($period->zaposlen?->user_id, $windowDays),
            'pause_from' => $period->datum_pocetka?->format('d.m.Y.'),
            'pause_to' => $period->datum_kraja?->format('d.m.Y.'),
        ]);

        return [
            'employees' => $employees,
            'last_analysis' => $periods->max(fn (PeriodRizika $p) => $p->created_at)?->format('d.m.Y. H:i'),
        ];
    }

    /**
     * Full risk overview for a single employee (header, KPIs, reason, incidents).
     *
     * @return array<string, mixed>
     */
    public function overview(Zaposlen $employee, PeriodRizika $break): array
    {
        $employee->loadMissing('user:id,first_name,last_name,email');

        $windowDays = (int) config('incidents.analysis.window_days');
        $threshold = (int) config('incidents.analysis.threshold');
        $pauseDays = (int) config('incidents.analysis.pause_days');

        $recentCount = $this->incidentCount($employee->user_id, $windowDays);
        $totalCount = Incident::query()
            ->whereHas('responsibleEmployees', fn (Builder $q) => $q->whereKey($employee->user_id))
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
            ->whereHas('responsibleEmployees', fn (Builder $q) => $q->whereKey($employee->user_id))
            ->where('occurred_at', '>=', now()->subDays($windowDays))
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (Incident $incident) => $this->serializeIncident($incident, $employee->user_id))
            ->values();

        $from = $break->datum_pocetka;
        $to = $break->datum_kraja;

        return [
            'employee' => [
                'user_id' => $employee->user_id,
                'name' => $this->employeeName($employee),
                'initials' => $this->initials($employee),
                'role' => $this->roleLabel($employee->role),
                'email' => $employee->user?->email,
            ],
            'pause' => [
                'from' => $from?->format('d.m.Y.'),
                'to' => $to?->format('d.m.Y.'),
                'duration_days' => ($from && $to) ? (int) $from->diffInDays($to) : $pauseDays,
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
        ];
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
