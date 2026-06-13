<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightTemplate extends Model
{
    protected $fillable = [
        'dispatcher_id',
        'name',
        'route_id',
        'departure_time',
        'duration_minutes',
        'min_capacity',
        'luxury_level',
        'notes',
    ];

    protected $casts = [
        'departure_time' => 'string',
        'duration_minutes' => 'integer',
        'min_capacity' => 'integer',
        'luxury_level' => 'integer',
    ];

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(Dispatcher::class, 'dispatcher_id', 'user_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
