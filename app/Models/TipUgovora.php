<?php

namespace App\Models;

use Database\Factories\TipUgovoraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipUgovora extends Model
{
    /** @use HasFactory<TipUgovoraFactory> */
    use HasFactory;

    protected $table = 'tipovi_ugovora';

    protected $fillable = ['naziv', 'opis'];

    public function zaposleni(): BelongsToMany
    {
        return $this->belongsToMany(Zaposlen::class, 'ugovori', 'tip_ugovora_id', 'zaposlen_user_id')
            ->using(Ugovor::class)
            ->withPivot(['datum_potpisivanja', 'datum_isteka', 'napomena'])
            ->withTimestamps();
    }
}
