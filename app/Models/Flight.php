<?php

namespace App\Models;

use App\Models\Concerns\HasHumanReadableNumber;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasHumanReadableNumber;

    protected $fillable = [
        'plane_id',
        'route_id',
        'dispatcher_id',
        'expected_takeoff',
        'expected_arrival',
        'status',
        'longitude',
        'latitude',
        'base_price',
        'current_price',
        'price_updated_at',
    ];

    protected $casts = [
        'expected_takeoff' => 'datetime',
        'expected_arrival' => 'datetime',
        'longitude' => 'decimal:6',
        'latitude' => 'decimal:6',
        'base_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'price_updated_at' => 'datetime',
    ];

    public static function numberPrefix(): string
    {
        return 'FL';
    }

    public function plane()
    {
        return $this->belongsTo(Plane::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function dispatcher()
    {
        return $this->belongsTo(Dispatcher::class, 'dispatcher_id', 'user_id');
    }

    public function failures()
    {
        return $this->hasMany(Failure::class);
    }

    public function routeChanges()
    {
        return $this->hasMany(RouteChange::class);
    }

    public function planeChanges()
    {
        return $this->hasMany(PlaneChange::class);
    }

    public function tickets()
    {
        return $this->hasMany(FlightTicket::class);
    }

    public function layovers()
    {
        return $this->hasManyThrough(Layover::class, Route::class, 'id', 'route_id', 'route_id', 'id');
    }
}
