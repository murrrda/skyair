<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeTrainingsRequest;
use App\Models\TrainingType;
use App\Models\Zaposlen;
use App\Services\EmployeeTrainingService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EmployeeTrainingController extends Controller
{
    /**
     * Step 3 (final) of the employee wizard: manage the employee's trainings.
     */
    public function edit(Zaposlen $employee)
    {
        Gate::authorize('is-admin');

        $employee->load('user');

        return Inertia::render('admin/ZaposlenObuke', [
            'zaposlen' => [
                'user_id' => $employee->user_id,
                'first_name' => $employee->user->first_name,
                'last_name' => $employee->user->last_name,
            ],
            'trainings' => $employee->trainings()
                ->orderByDesc('started_at')
                ->get(['id', 'training_type_id', 'started_at', 'finished_at', 'note']),
            'trainingTypes' => TrainingType::query()->active()->orderBy('name')
                ->get(['id', 'name', 'duration_days']),
            'mode' => session()->has('pendingEmployeeCredentials') ? 'create' : 'edit',
        ]);
    }

    public function update(StoreEmployeeTrainingsRequest $request, Zaposlen $employee, EmployeeTrainingService $service)
    {
        $service->sync($employee, $request->validated()['trainings']);

        $redirect = redirect()->route('admin.employee.index')
            ->with('success', 'Obuke zaposlenika su sačuvane.');

        // Final wizard step — surface the credentials generated in step 1.
        if ($credentials = session()->pull('pendingEmployeeCredentials')) {
            $redirect->with('newEmployeeCredentials', $credentials);
        }

        return $redirect;
    }
}
