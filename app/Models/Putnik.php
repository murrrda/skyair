<?php

namespace App\Models;

use Database\Factories\PutnikFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Putnik extends Model
{
    /** @use HasFactory<PutnikFactory> */
    use HasFactory;

    protected $table = 'putnici';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'credit_card_number',
        'status_points',
        'reward_points',
        'tier',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
