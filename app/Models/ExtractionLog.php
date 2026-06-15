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
        'validation_results',
    ];

    protected function casts(): array
    {
        return [
            'extracted_fields' => 'array',
            'validation_results' => 'array',
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
