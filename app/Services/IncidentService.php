<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function __construct(private IncidentAnalysisService $analysis) {}

    /**
     * Record a new incident, attach the responsible employees, and run the
     * automatic risk analysis on them.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $responsibleEmployeeIds
     */
    public function record(array $data, array $responsibleEmployeeIds = []): Incident
    {
        return DB::transaction(function () use ($data, $responsibleEmployeeIds) {
            $incident = Incident::create($data);

            $incident->responsibleEmployees()->sync($responsibleEmployeeIds);

            $this->analysis->analyze($incident);

            return $incident;
        });
    }
}
