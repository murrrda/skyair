<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Ugovor extends Pivot
{
    protected $table = 'ugovori';

    protected $fillable = [
        'zaposlen_user_id',
        'tip_ugovora_id',
        'datum_potpisivanja',
        'datum_isteka',
        'napomena',
        'is_active',
    ];

    protected $casts = [
        'datum_potpisivanja' => 'date',
        'datum_isteka' => 'date',
        'is_active' => 'boolean',
    ];
}
