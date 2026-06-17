<?php

namespace App\Models;

use Database\Factories\TrainingTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingType extends Model
{
    /** @use HasFactory<TrainingTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'duration_days',
        'is_active',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
