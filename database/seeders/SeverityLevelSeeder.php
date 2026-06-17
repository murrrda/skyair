<?php

namespace Database\Seeders;

use App\Models\SeverityLevel;
use Illuminate\Database\Seeder;

class SeverityLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Nizak', 'description' => 'Manji incident bez posledica po bezbednost.', 'rank' => 1],
            ['name' => 'Srednji', 'description' => 'Incident sa ograničenim uticajem na let.', 'rank' => 2],
            ['name' => 'Visok', 'description' => 'Ozbiljan incident sa značajnim rizikom.', 'rank' => 3],
            ['name' => 'Kritičan', 'description' => 'Kritičan incident koji ugrožava bezbednost leta.', 'rank' => 4],
        ];

        foreach ($levels as $level) {
            SeverityLevel::firstOrCreate(['name' => $level['name']], $level);
        }
    }
}
