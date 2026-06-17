<?php

namespace App\Services;

use App\Models\Zaposlen;
use Illuminate\Support\Facades\DB;

class EmployeeCertificateService
{
    /**
     * Reconcile an employee's certificate list with the submitted rows.
     *
     * New rows (no id) are created, existing rows are updated, and any row
     * the administrator removed from the form is soft-deleted — preserving it
     * as history.
     *
     * @param  array<int, array{id?: int|null, certificate_type_id: int, issued_at: string, expires_at: string, note?: string|null}>  $certificates
     */
    public function sync(Zaposlen $employee, array $certificates): void
    {
        DB::transaction(function () use ($employee, $certificates) {
            $keptIds = [];

            foreach ($certificates as $certificate) {
                $model = $employee->certificates()->updateOrCreate(
                    ['id' => $certificate['id'] ?? null],
                    [
                        'certificate_type_id' => $certificate['certificate_type_id'],
                        'issued_at' => $certificate['issued_at'],
                        'expires_at' => $certificate['expires_at'],
                        'note' => $certificate['note'] ?? null,
                    ]
                );

                $keptIds[] = $model->id;
            }

            $employee->certificates()->whereNotIn('id', $keptIds)->delete();
        });
    }
}
