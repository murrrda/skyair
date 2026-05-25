<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationState extends Model
{
    protected $fillable = ['reservation_id', 'status'];
}
