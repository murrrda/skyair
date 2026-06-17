<?php

namespace App\Models;

use Database\Factories\CertificateTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    /** @use HasFactory<CertificateTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_validity_months',
        'is_active',
    ];

    protected $casts = [
        'default_validity_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
