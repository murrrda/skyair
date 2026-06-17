<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use Illuminate\Database\Seeder;

class CertificateTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Pilotska licenca', 'description' => 'Dozvola za upravljanje vazduhoplovom.', 'default_validity_months' => 60],
            ['name' => 'Ljekarsko uvjerenje', 'description' => 'Potvrda o zdravstvenoj sposobnosti za letenje.', 'default_validity_months' => 12],
            ['name' => 'Prva pomoć', 'description' => 'Certifikat o osposobljenosti za pružanje prve pomoći.', 'default_validity_months' => 24],
        ];

        foreach ($types as $type) {
            CertificateType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
