<?php

namespace App\Models;

use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flight_id',
        'incident_type_id',
        'severity_level_id',
        'occurred_at',
        'description',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }

    public function severity(): BelongsTo
    {
        return $this->belongsTo(SeverityLevel::class, 'severity_level_id');
    }

    public function responsibleEmployees(): BelongsToMany
    {
        return $this->belongsToMany(
            Zaposlen::class,
            'incident_employee',
            'incident_id',
            'zaposlen_user_id',
            'id',
            'user_id',
        );
    }
}
