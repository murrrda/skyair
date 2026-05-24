<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketWorkLog extends Model
{
    protected $table = 'support_ticket_work_log';

    protected $fillable = [
        'support_ticket_id',
        'employee_id',
        'started_at',
        'ended_at',
        'action',
        'note',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Zaposlen::class, 'employee_id', 'user_id');
    }
}