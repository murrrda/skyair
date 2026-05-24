<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketFieldValue extends Model
{
    protected $table = 'support_ticket_field_value';

    protected $fillable = [
        'value',
    ];

    public function supportTicket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function categoryField()
    {
        return $this->belongsTo(CategoryField::class);
    }
}