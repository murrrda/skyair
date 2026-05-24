<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layover extends Model
{
    protected $fillable = [
        'route_id',
        'airport_id',
        'stop_order',
        'expected_stay',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
