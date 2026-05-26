<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'payment_id',
        'latest_state_id',
        'cancellation_id',
        'total_price',
        'code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function tickets()
    {
        return $this->hasMany(FlightTicket::class);
    }

    public function states()
    {
        return $this->hasMany(ReservationState::class);
    }

    public function latestState()
    {
        return $this->belongsTo(ReservationState::class, 'latest_state_id');
    }

    public function cancellation()
    {
        return $this->belongsTo(Cancellation::class);
    }
}
