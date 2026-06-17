<?php

namespace Database\Seeders;

use App\Models\Uloga;
use Illuminate\Database\Seeder;

class UlogaSeeder extends Seeder
{
    /**
     * Codebook of crew roles on a flight. `code` mirrors Zaposlen.role.
     */
    public function run(): void
    {
        $uloge = [
            ['code' => 'pilot', 'naziv' => 'Kapetan', 'opis' => 'Vođa vazduhoplova.'],
            ['code' => 'co_pilot', 'naziv' => 'Kopilot', 'opis' => 'Drugi pilot vazduhoplova.'],
            ['code' => 'cabin_crew', 'naziv' => 'Kabinsko osoblje', 'opis' => 'Član kabinske posade.'],
        ];

        foreach ($uloge as $uloga) {
            Uloga::updateOrCreate(['code' => $uloga['code']], $uloga);
        }
    }
}
