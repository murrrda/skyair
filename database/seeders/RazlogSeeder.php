<?php

namespace Database\Seeders;

use App\Models\Razlog;
use App\Services\IncidentAnalysisService;
use Illuminate\Database\Seeder;

class RazlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $razlozi = [
            IncidentAnalysisService::REASON,
        ];

        foreach ($razlozi as $naziv) {
            Razlog::firstOrCreate(['naziv' => $naziv]);
        }
    }
}
