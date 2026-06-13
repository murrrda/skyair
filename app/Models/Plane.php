<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plane extends Model
{
    protected $fillable = [
        'reg_number',
        'admin_id',
        'model',
        'capacity',
        'luxury_level',
        'range_km',
        'max_speed',
        'repair_service_interval',
        'model_year',
        'status',
        'commissioned_at',
        'total_mileage',
        'total_flight_hours',
        'hours_since_last_service',
    ];

    protected $casts = [
        'commissioned_at' => 'date',
    ];

    public function admin()
    {
        return $this->belongsTo(Zaposlen::class, 'admin_id', 'user_id');
    }

    public function flights()
    {
        return $this->hasMany(Flight::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function failures()
    {
        return $this->hasMany(Failure::class);
    }
}
