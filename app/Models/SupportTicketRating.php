<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketRating extends Model
{
    protected $table = 'support_ticket_rating';

    protected $fillable = [
        'support_ticket_id',
        'resolution_speed',
        'communication_quality',
        'degree_of_resolution',
    ];

    protected $casts = [
        'resolution_speed' => 'integer',
        'communication_quality' => 'integer',
        'degree_of_resolution' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function agentRatings(): HasMany
    {
        return $this->hasMany(SupportTicketRatingAgent::class, 'support_ticket_rating_id');
    }
}
