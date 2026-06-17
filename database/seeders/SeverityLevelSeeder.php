<?php

namespace Database\Seeders;

use App\Models\SeverityLevel;
use Illuminate\Database\Seeder;

class SeverityLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Low', 'description' => 'Manji uticaj', 'rank' => 1],
            ['name' => 'Medium', 'description' => 'Umereni uticaj', 'rank' => 2],
            ['name' => 'High', 'description' => 'Visoki uticaj', 'rank' => 3],
            ['name' => 'Critical', 'description' => 'Kritična situacija', 'rank' => 4],
        ];

        foreach ($levels as $level) {
            SeverityLevel::firstOrCreate(['name' => $level['name']], $level);
        }
    }
}
