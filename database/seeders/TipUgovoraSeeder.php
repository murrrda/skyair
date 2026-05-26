<?php

namespace Database\Seeders;

use App\Models\TipUgovora;
use Illuminate\Database\Seeder;

class TipUgovoraSeeder extends Seeder
{
    public function run(): void
    {
        $tipovi = [
            ['naziv' => 'Ugovor o radu na neodređeno', 'opis' => 'Stalni radni odnos bez utvrđenog datuma isteka.'],
            ['naziv' => 'Ugovor o radu na određeno', 'opis' => 'Radni odnos na određeno vremensko razdoblje.'],
            ['naziv' => 'Ugovor o djelu', 'opis' => 'Angažman za izvršenje određenog posla ili projekta.'],
            ['naziv' => 'Ugovor o volontiranju', 'opis' => 'Volonterski angažman bez naknade.'],
            ['naziv' => 'Sezonski ugovor o radu', 'opis' => 'Privremeni ugovor za sezonske poslove.'],
        ];

        foreach ($tipovi as $tip) {
            TipUgovora::firstOrCreate(['naziv' => $tip['naziv']], $tip);
        }
    }
}
