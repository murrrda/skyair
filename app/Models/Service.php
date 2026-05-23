<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'plane_id',
        'admin_id',
        'started',
        'ended',
        'status',
        'description',
        'price',
        'service_center',
    ];

    protected $casts = [
        'started' => 'datetime',
        'ended' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function plane()
    {
        return $this->belongsTo(Plane::class);
    }

    public function admin()
    {
        return $this->belongsTo(Zaposlen::class, 'admin_id', 'user_id');
    }
}
