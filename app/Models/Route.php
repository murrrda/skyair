<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'starting_airport_id',
        'landing_airport_id',
        'admin_id',
        'name',
        'distance_km',
        'estimated_time',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function admin()
    {
        return $this->belongsTo(Zaposlen::class, 'admin_id', 'user_id');
    }

    public function flights()
    {
        return $this->hasMany(Flight::class);
    }

    public function layovers()
    {
        return $this->hasMany(Layover::class);
    }
}
