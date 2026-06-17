<?php

namespace Database\Seeders;

use App\Models\IncidentType;
use Illuminate\Database\Seeder;

class IncidentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Tehnički kvar', 'description' => 'Kvar na vazduhoplovu ili opremi tokom leta.'],
            ['name' => 'Greška posade', 'description' => 'Propust ili greška u radu članova posade.'],
            ['name' => 'Narušavanje bezbednosti', 'description' => 'Bezbednosni incident na letu ili u kabini.'],
            ['name' => 'Medicinski incident', 'description' => 'Zdravstveni problem putnika ili člana posade.'],
            ['name' => 'Vremenski uslovi', 'description' => 'Incident izazvan nepovoljnim vremenskim uslovima.'],
            ['name' => 'Prinudno sletanje', 'description' => 'Neplanirano ili prinudno sletanje vazduhoplova.'],
        ];

        foreach ($types as $type) {
            IncidentType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
