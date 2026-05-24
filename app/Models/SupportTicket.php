<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const STATUS_LABELS = [
        'open' => 'Otvoren',
        'in_progress' => 'U rešavanju',
        'requires_info' => 'Zahteva informacije',
        'transferred' => 'Prosleđen',
        'closed' => 'Završen',
    ];

    public static function statusLabel(?string $status): string
    {
        if ($status === null) {
            return '—';
        }

        return self::STATUS_LABELS[$status] ?? $status;
    }

    protected $table = 'support_ticket';

    protected $fillable = [
        'description',
        'number',
        'status',
        'priority',
        'category_id',
        'outcome',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(SupportTicketFieldValue::class);
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(SupportTicketWorkLog::class)->orderBy('started_at');
    }

    public function activeWorkLog()
    {
        return $this->hasOne(SupportTicketWorkLog::class)->whereNull('ended_at')->latestOfMany('started_at');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(SupportTicketChangeLog::class)->orderBy('created_at');
    }
}