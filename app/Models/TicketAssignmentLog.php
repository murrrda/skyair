<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAssignmentLog extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'assigned_employee_id',
        'candidate_scores',
        'outcome',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'candidate_scores' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Zaposlen::class, 'assigned_employee_id', 'user_id');
    }
}
