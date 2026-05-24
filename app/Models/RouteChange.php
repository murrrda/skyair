<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteChange extends Model
{
    protected $fillable = [
        'flight_id',
        'original_route_id',
        'new_route_id',
        'dispatcher_id',
        'requested_at',
        'applied_at',
        'status',
        'reason',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function originalRoute()
    {
        return $this->belongsTo(Route::class, 'original_route_id');
    }

    public function newRoute()
    {
        return $this->belongsTo(Route::class, 'new_route_id');
    }

    public function dispatcher()
    {
        return $this->belongsTo(Dispatcher::class, 'dispatcher_id', 'user_id');
    }
}
