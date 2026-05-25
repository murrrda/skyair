<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightTicket extends Model
{
    protected $fillable = [
        'passenger_first_name',
        'passenger_last_name',
        'flight_id',
        'reservation_id',
        'ticket_class_id',
        'baggage_id',
        'base_price',
        'final_price',
        'seat_number',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function ticketClass(): BelongsTo
    {
        return $this->belongsTo(TicketClass::class);
    }

    public function baggage(): BelongsTo
    {
        return $this->belongsTo(Baggage::class);
    }
}
