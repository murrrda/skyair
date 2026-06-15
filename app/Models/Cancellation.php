<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model
{
    protected $fillable = [
        'reason',
        'note',
        'cancellation_fee',
        'refund_amount',
        'reward_points_refunded',
    ];

    protected $casts = [
        'cancellation_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];
}
