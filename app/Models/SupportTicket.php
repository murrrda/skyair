<?php

namespace App\Models;

use App\Models\Concerns\HasHumanReadableNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportTicket extends Model
{
    use HasHumanReadableNumber;

    public const STATUS_LABELS = [
        'draft' => 'Nacrt',
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

    public static function numberPrefix(): string
    {
        return 'ST';
    }

    protected $fillable = [
        'user_id',
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

    public function rating(): HasOne
    {
        return $this->hasOne(SupportTicketRating::class);
    }

    public function extractionLogs(): HasMany
    {
        return $this->hasMany(ExtractionLog::class);
    }
}
