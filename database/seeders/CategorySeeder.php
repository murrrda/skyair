<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Izgubljen prtljag',
            'Oštećen prtljag',
            'Kašnjenje leta',
            'Otkazan let',
            'Refundacija',
            'Sedišta',
            'Hrana / obrok',
            'Loyalty program',
            'Ostalo',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}