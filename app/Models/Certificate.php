<?php

namespace App\Models;

use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'zaposlen_user_id',
        'certificate_type_id',
        'issued_at',
        'expires_at',
        'note',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    protected $appends = ['status'];

    public function zaposlen(): BelongsTo
    {
        return $this->belongsTo(Zaposlen::class, 'zaposlen_user_id', 'user_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    /**
     * A certificate is active while today is before its expiry date.
     *
     * @return Attribute<string, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): string => $this->expires_at?->isFuture() ? 'active' : 'expired');
    }
}
