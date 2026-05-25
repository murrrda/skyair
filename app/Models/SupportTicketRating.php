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
        'resolution_speed_comment',
        'communication_quality',
        'communication_quality_comment',
        'degree_of_resolution',
        'degree_of_resolution_comment',
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
