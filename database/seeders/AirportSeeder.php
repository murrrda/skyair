<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            ['iata_code' => 'BEG', 'name' => 'Nikola Tesla',           'city' => 'Beograd',    'country' => 'Srbija'],
            ['iata_code' => 'CDG', 'name' => 'Charles de Gaulle',      'city' => 'Pariz',      'country' => 'Francuska'],
            ['iata_code' => 'FRA', 'name' => 'Frankfurt am Main',      'city' => 'Frankfurt',  'country' => 'Nemačka'],
            ['iata_code' => 'MUC', 'name' => 'Franz Josef Strauss',    'city' => 'Minhen',     'country' => 'Nemačka'],
            ['iata_code' => 'VIE', 'name' => 'Schwechat',              'city' => 'Beč',        'country' => 'Austrija'],
            ['iata_code' => 'ZRH', 'name' => 'Kloten',                 'city' => 'Cirih',      'country' => 'Švajcarska'],
            ['iata_code' => 'AMS', 'name' => 'Schiphol',               'city' => 'Amsterdam',  'country' => 'Holandija'],
            ['iata_code' => 'IST', 'name' => 'Istanbul',               'city' => 'Istanbul',   'country' => 'Turska'],
            ['iata_code' => 'ATH', 'name' => 'Eleftherios Venizelos',  'city' => 'Atina',      'country' => 'Grčka'],
            ['iata_code' => 'SOF', 'name' => 'Vasil Levski',           'city' => 'Sofija',     'country' => 'Bugarska'],
        ];

        foreach ($airports as $airport) {
            Airport::updateOrCreate(
                ['iata_code' => $airport['iata_code']],
                $airport,
            );
        }
    }
}
