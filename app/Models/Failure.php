<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Failure extends Model
{
    protected $fillable = [
        'plane_id',
        'flight_id',
        'report_time',
        'description',
        'seriousness_level',
        'status',
    ];

    protected $casts = [
        'report_time' => 'datetime',
        'seriousness_level' => 'integer',
    ];

    public function plane()
    {
        return $this->belongsTo(Plane::class);
    }

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }
}
