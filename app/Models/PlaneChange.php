<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaneChange extends Model
{
    protected $fillable = [
        'flight_id',
        'original_plane_id',
        'new_plane_id',
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

    public function originalPlane()
    {
        return $this->belongsTo(Plane::class, 'original_plane_id');
    }

    public function newPlane()
    {
        return $this->belongsTo(Plane::class, 'new_plane_id');
    }

    public function dispatcher()
    {
        return $this->belongsTo(Dispatcher::class, 'dispatcher_id', 'user_id');
    }
}
