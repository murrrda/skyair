<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    /**
     * Record a new incident and attach the responsible employees.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $responsibleEmployeeIds
     */
    public function record(array $data, array $responsibleEmployeeIds = []): Incident
    {
        return DB::transaction(function () use ($data, $responsibleEmployeeIds) {
            $incident = Incident::create($data);

            $incident->responsibleEmployees()->sync($responsibleEmployeeIds);

            return $incident;
        });
    }
}
