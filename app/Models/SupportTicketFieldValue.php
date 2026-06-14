<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketFieldValue extends Model
{
    protected $table = 'support_ticket_field_value';

    protected $fillable = [
        'category_field_id',
        'value',
        'confidence',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
        ];
    }

    public function supportTicket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function categoryField()
    {
        return $this->belongsTo(CategoryField::class);
    }
}
