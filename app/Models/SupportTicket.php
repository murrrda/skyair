<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
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
}