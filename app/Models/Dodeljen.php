<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dodeljen extends Model
{
    protected $table = 'dodeljeni';

    protected $fillable = [
        'flight_id',
        'zaposlen_user_id',
        'uloga_id',
        'status',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class, 'flight_id');
    }

    public function zaposlen(): BelongsTo
    {
        return $this->belongsTo(Zaposlen::class, 'zaposlen_user_id', 'user_id');
    }

    public function uloga(): BelongsTo
    {
        return $this->belongsTo(Uloga::class, 'uloga_id');
    }
}
