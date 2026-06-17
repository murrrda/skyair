<?php

namespace App\Models;

use Database\Factories\EmployeeTrainingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeTraining extends Model
{
    /** @use HasFactory<EmployeeTrainingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'zaposlen_user_id',
        'training_type_id',
        'started_at',
        'finished_at',
        'note',
    ];

    protected $casts = [
        'started_at' => 'date',
        'finished_at' => 'date',
    ];

    public function zaposlen(): BelongsTo
    {
        return $this->belongsTo(Zaposlen::class, 'zaposlen_user_id', 'user_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class, 'training_type_id');
    }
}
