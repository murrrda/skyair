<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Razlog extends Model
{
    /** @use HasFactory<\Database\Factories\RazlogFactory> */
    use HasFactory;

    protected $table = 'razlozi';

    protected $fillable = ['naziv'];

    public function periodiRizika(): HasMany
    {
        return $this->hasMany(PeriodRizika::class);
    }
}
