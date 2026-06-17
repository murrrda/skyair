<?php

namespace App\Services;

use App\Models\Zaposlen;
use Illuminate\Support\Facades\DB;

class EmployeeTrainingService
{
    /**
     * Reconcile an employee's training list with the submitted rows.
     *
     * New rows (no id) are created, existing rows are updated, and any row
     * the administrator removed from the form is soft-deleted — preserving it
     * as history.
     *
     * @param  array<int, array{id?: int|null, training_type_id: int, started_at: string, finished_at: string, note?: string|null}>  $trainings
     */
    public function sync(Zaposlen $employee, array $trainings): void
    {
        DB::transaction(function () use ($employee, $trainings) {
            $keptIds = [];

            foreach ($trainings as $training) {
                $model = $employee->trainings()->updateOrCreate(
                    ['id' => $training['id'] ?? null],
                    [
                        'training_type_id' => $training['training_type_id'],
                        'started_at' => $training['started_at'],
                        'finished_at' => $training['finished_at'],
                        'note' => $training['note'] ?? null,
                    ]
                );

                $keptIds[] = $model->id;
            }

            $employee->trainings()->whereNotIn('id', $keptIds)->delete();
        });
    }
}
