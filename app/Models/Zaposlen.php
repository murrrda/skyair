<?php

namespace App\Models;

use Database\Factories\ZaposlenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Zaposlen extends Model
{
    /** @use HasFactory<ZaposlenFactory> */
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
        'datum_otkaza' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoviUgovora(): BelongsToMany
    {
        return $this->belongsToMany(TipUgovora::class, 'ugovori', 'zaposlen_user_id', 'tip_ugovora_id')
            ->using(Ugovor::class)
            ->withPivot(['datum_potpisivanja', 'datum_isteka', 'napomena'])
            ->withTimestamps();
    }
}
