<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    protected $fillable = [
        'user_id',
        'credit_card_number',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
