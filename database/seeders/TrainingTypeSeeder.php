<?php

namespace Database\Seeders;

use App\Models\TrainingType;
use Illuminate\Database\Seeder;

class TrainingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Osnovna obuka za tip aviona', 'description' => 'Početna obuka za upravljanje određenim tipom vazduhoplova (type rating).', 'category' => 'type_rating', 'duration_days' => 30],
            ['name' => 'Obuka na simulatoru — Full Flight', 'description' => 'Obuka na full-flight simulatoru.', 'category' => 'simulator', 'duration_days' => 5],
            ['name' => 'Recurrent obuka', 'description' => 'Periodična obuka za održavanje ovlašćenja.', 'category' => 'recurrent', 'duration_days' => 3],
            ['name' => 'CRM obuka', 'description' => 'Crew Resource Management — saradnja i komunikacija posade.', 'category' => 'safety', 'duration_days' => 1],
            ['name' => 'Obuka iz bezbednosti i evakuacije', 'description' => 'Postupci u vanrednim situacijama i evakuacija.', 'category' => 'safety', 'duration_days' => 2],
        ];

        foreach ($types as $type) {
            TrainingType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
