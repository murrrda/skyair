<?php

namespace App\Models;

use Database\Factories\SeverityLevelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeverityLevel extends Model
{
    /** @use HasFactory<SeverityLevelFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'rank',
        'is_active',
    ];

    protected $casts = [
        'rank' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
