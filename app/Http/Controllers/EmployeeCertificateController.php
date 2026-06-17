<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeCertificatesRequest;
use App\Models\CertificateType;
use App\Models\Zaposlen;
use App\Services\EmployeeCertificateService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EmployeeCertificateController extends Controller
{
    /**
     * Step 2 of the employee wizard: manage the employee's certificates.
     */
    public function edit(Zaposlen $employee)
    {
        Gate::authorize('is-admin');

        $employee->load('user');

        return Inertia::render('admin/ZaposlenSertifikati', [
            'zaposlen' => [
                'user_id' => $employee->user_id,
                'first_name' => $employee->user->first_name,
                'last_name' => $employee->user->last_name,
            ],
            'certificates' => $employee->certificates()
                ->orderByDesc('issued_at')
                ->get(['id', 'certificate_type_id', 'issued_at', 'expires_at', 'note']),
            'certificateTypes' => CertificateType::query()->active()->orderBy('name')
                ->get(['id', 'name', 'default_validity_months']),
        ]);
    }

    public function update(StoreEmployeeCertificatesRequest $request, Zaposlen $employee, EmployeeCertificateService $service)
    {
        $service->sync($employee, $request->validated()['certificates']);

        // Continue to step 3 (trainings); the pending credentials stay in the
        // session and are surfaced once that final step is saved.
        return redirect()->route('admin.employee.trainings.edit', $employee)
            ->with('success', 'Sertifikati zaposlenika su sačuvani.');
    }
}
