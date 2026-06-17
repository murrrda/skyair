<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Razlog;
use App\Models\Zaposlen;

class IncidentAnalysisService
{
    /**
     * Reason recorded on a risk period opened by the incident analysis.
     */
    public const REASON = 'Prekoračen dozvoljen broj incidenata';

    /**
     * Re-evaluate every employee held responsible for the given incident and
     * open a risk period for anyone who has crossed the incident threshold.
     */
    public function analyze(Incident $incident): void
    {
        $incident->loadMissing('responsibleEmployees');

        foreach ($incident->responsibleEmployees as $employee) {
            $this->evaluate($employee);
        }
    }

    /**
     * Flag a single employee as risky when their recent incident count reaches
     * the configured threshold and they are not already on a break for it.
     */
    private function evaluate(Zaposlen $employee): void
    {
        $windowDays = (int) config('incidents.analysis.window_days');
        $threshold = (int) config('incidents.analysis.threshold');

        $recentCount = Incident::query()
            ->whereHas('responsibleEmployees', fn ($query) => $query->whereKey($employee->user_id))
            ->where('occurred_at', '>=', now()->subDays($windowDays))
            ->count();

        if ($recentCount < $threshold) {
            return;
        }

        $razlog = Razlog::firstOrCreate(['naziv' => self::REASON]);

        $alreadyOnBreak = $employee->periodiRizika()
            ->whereNull('datum_kraja')
            ->where('razlog_id', $razlog->id)
            ->exists();

        if ($alreadyOnBreak) {
            return;
        }

        $employee->periodiRizika()->create([
            'datum_pocetka' => now()->toDateString(),
            'datum_kraja' => null,
            'razlog_id' => $razlog->id,
        ]);
    }
}
