<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodRizika extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodRizikaFactory> */
    use HasFactory;

    protected $table = 'periodi_rizika';

    protected $fillable = ['datum_pocetka', 'datum_kraja', 'razlog_id', 'zaposlen_id'];

    protected $casts = [
        'datum_pocetka' => 'date',
        'datum_kraja'   => 'date',
    ];

    public function razlog(): BelongsTo
    {
        return $this->belongsTo(Razlog::class);
    }

    public function zaposlen(): BelongsTo
    {
        return $this->belongsTo(Zaposlen::class, 'zaposlen_id', 'user_id');
    }

    public function scopeAktivni(Builder $query): void
    {
        $query->whereNull('datum_kraja')->orWhere('datum_kraja', '>', now());
    }
}
