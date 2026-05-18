<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $table = 'support_ticket';

    protected $fillable = [
        'description',
        'number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function fieldValues()
    {
        return $this->hasMany(SupportTicketFieldValue::class);
    }
}