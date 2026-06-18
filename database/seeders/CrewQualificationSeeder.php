<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\EmployeeTraining;
use App\Models\TrainingType;
use App\Models\Zaposlen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CrewQualificationSeeder extends Seeder
{
    /**
     * Give every active flight-crew member a valid certificate and a completed
     * training so they pass the qualification checks in CrewAssignmentService.
     */
    public function run(): void
    {
        $this->call(CertificateTypeSeeder::class);
        $this->call(TrainingTypeSeeder::class);

        $license = CertificateType::where('name', 'Pilotska licenca')->first()
            ?? CertificateType::first();
        $training = TrainingType::where('name', 'Osnovna obuka za tip aviona')->first()
            ?? TrainingType::first();

        if ($license === null || $training === null) {
            $this->command?->warn('CrewQualificationSeeder: missing certificate/training types, skipping.');

            return;
        }

        $crew = Zaposlen::whereIn('role', ['pilot', 'co_pilot', 'cabin_crew'])
            ->where('status', 'aktivan')
            ->get();

        foreach ($crew as $z) {
            Certificate::firstOrCreate(
                ['zaposlen_user_id' => $z->user_id, 'certificate_type_id' => $license->id],
                [
                    'issued_at' => Carbon::now()->subMonths(6),
                    'expires_at' => Carbon::now()->addYears(2),
                    'note' => 'Seed: važeća licenca',
                ],
            );

            EmployeeTraining::firstOrCreate(
                ['zaposlen_user_id' => $z->user_id, 'training_type_id' => $training->id],
                [
                    'started_at' => Carbon::now()->subMonths(7),
                    'finished_at' => Carbon::now()->subMonths(6),
                    'note' => 'Seed: završena obuka',
                ],
            );
        }

        $this->command?->info('CrewQualificationSeeder: qualified '.$crew->count().' active crew members.');
    }
}
