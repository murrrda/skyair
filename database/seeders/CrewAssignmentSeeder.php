<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\EmployeeTraining;
use App\Models\Flight;
use App\Models\TrainingType;
use App\Models\Zaposlen;
use App\Services\CrewAssignmentService;
use Illuminate\Database\Seeder;

class CrewAssignmentSeeder extends Seeder
{
    /**
     * Ensure flight crew (pilots, co-pilots, cabin crew) are qualified,
     * then auto-assign them to every flight that has no crew yet.
     *
     * Defensive: no-ops gracefully when employees or flights are absent.
     */
    public function run(CrewAssignmentService $crewAssignment): void
    {
        $this->call(UlogaSeeder::class);

        $certType = CertificateType::query()->first();
        $trainingType = TrainingType::query()->first();

        $crew = Zaposlen::query()
            ->whereIn('role', array_keys(CrewAssignmentService::REQUIRED_CREW))
            ->where('status', 'aktivan')
            ->get();

        foreach ($crew as $zaposlen) {
            if ($certType && $zaposlen->certificates()->count() === 0) {
                Certificate::create([
                    'zaposlen_user_id' => $zaposlen->user_id,
                    'certificate_type_id' => $certType->id,
                    'issued_at' => now()->subYear(),
                    'expires_at' => now()->addYears(2),
                ]);
            }

            if ($trainingType && $zaposlen->trainings()->count() === 0) {
                EmployeeTraining::create([
                    'zaposlen_user_id' => $zaposlen->user_id,
                    'training_type_id' => $trainingType->id,
                    'started_at' => now()->subMonths(6),
                    'finished_at' => now()->subMonths(5),
                ]);
            }
        }

        Flight::query()
            ->doesntHave('crewAssignments')
            ->get()
            ->each(fn (Flight $flight) => $crewAssignment->assign($flight));
    }
}
