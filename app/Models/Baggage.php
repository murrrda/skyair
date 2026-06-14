<?php

namespace App\Models;

use App\Models\Concerns\HasHumanReadableNumber;
use Illuminate\Database\Eloquent\Model;

class Baggage extends Model
{
    use HasHumanReadableNumber;

    protected $table = 'baggages';

    public static function numberPrefix(): string
    {
        return 'BG';
    }
}
