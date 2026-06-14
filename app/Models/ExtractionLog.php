<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractionLog extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'category_id',
        'description',
        'extracted_fields',
        'raw_response',
        'model_used',
        'confidence_threshold',
    ];

    protected function casts(): array
    {
        return [
            'extracted_fields' => 'array',
            'confidence_threshold' => 'float',
        ];
    }

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
