<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\EmployeeTraining;
use App\Models\Flight;
use App\Models\TrainingType;
use App\Models\User;
use App\Models\Zaposlen;
use App\Services\CrewAssignmentService;
use Illuminate\Database\Seeder;

/**
 * Non-destructive test fixture for CrewAssignmentService.
 *
 * Creates a small pool of qualified flight crew (pilots, co-pilots and
 * cabin crew — each with a valid certificate and a completed training),
 * then auto-assigns them to every flight that has no crew yet.
 *
 * Idempotent: re-running adds nothing new and re-assigns only un-crewed
 * flights. Unlike ZaposlenSeeder it wipes no existing data.
 */
class CrewTestSeeder extends Seeder
{
    public function run(CrewAssignmentService $crewAssignment): void
    {
        $this->call(UlogaSeeder::class);

        // [first, last, gender, role] — a pool with redundancy so the
        // rest and weekly-hour constraints have alternatives to fall back on.
        $crew = [
            ['Vuk', 'Vukovic', 'male', 'pilot'],
            ['Lazar', 'Lazic', 'male', 'pilot'],
            ['Filip', 'Filipovic', 'male', 'co_pilot'],
            ['Dusan', 'Dusanovic', 'male', 'co_pilot'],
            ['Sara', 'Saric', 'female', 'cabin_crew'],
            ['Teodora', 'Teodorovic', 'female', 'cabin_crew'],
            ['Marija', 'Maric', 'female', 'cabin_crew'],
            ['Nina', 'Ninic', 'male', 'cabin_crew'],
        ];

        $certType = CertificateType::query()->first();
        $trainingType = TrainingType::query()->first();

        foreach ($crew as $index => [$first, $last, $gender, $role]) {
            $n = 9000 + $index;

            $user = User::firstOrCreate(
                ['email' => strtolower($first.'.'.$last).'@skyair.com'],
                [
                    'name' => $first.' '.$last,
                    'first_name' => $first,
                    'last_name' => $last,
                    'password' => 'password',
                    'date_of_birth' => '1990-01-01',
                    'jmbg' => str_pad((string) (2000000000000 + $n), 13, '0', STR_PAD_LEFT),
                    'phone_number' => '+381 60 '.(2000000 + $n),
                    'address' => 'Posada '.$n.', Beograd',
                    'gender' => $gender,
                    'country' => 'Srbija',
                ],
            );

            $zaposlen = Zaposlen::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'role' => $role,
                    'datum_zaposlenja' => '2022-01-01',
                    'status' => 'aktivan',
                    'datum_otkaza' => null,
                ],
            );

            // Qualify: a valid (non-expired) certificate and a completed training.
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
