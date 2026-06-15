<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketRatingAgent extends Model
{
    protected $table = 'support_ticket_rating_agent';

    protected $fillable = [
        'support_ticket_rating_id',
        'employee_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function ticketRating(): BelongsTo
    {
        return $this->belongsTo(SupportTicketRating::class, 'support_ticket_rating_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Zaposlen::class, 'employee_id', 'user_id');
    }
}
