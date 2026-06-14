<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketChangeLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'support_ticket_change_log';

    protected $fillable = [
        'support_ticket_id',
        'changed_by_user_id',
        'field',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
