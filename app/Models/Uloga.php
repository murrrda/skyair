<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Uloga extends Model
{
    protected $table = 'uloge';

    protected $fillable = [
        'code',
        'naziv',
        'opis',
    ];

    public function dodeljeni(): HasMany
    {
        return $this->hasMany(Dodeljen::class, 'uloga_id');
    }
}
