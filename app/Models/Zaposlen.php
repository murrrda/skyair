<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zaposlen extends Model
{
    /** @use HasFactory<\Database\Factories\ZaposlenFactory> */
    use HasFactory;

    protected $table = 'zaposleni';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'role',
        'datum_zaposlenja',
        'status',
        'datum_otkaza',
        'razlog_otkaza',
    ];

    protected $casts = [
        'datum_zaposlenja' => 'date',
        'datum_otkaza'     => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
