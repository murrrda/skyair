<?php

namespace App\Observers;

use App\Models\Putnik;
use App\Models\Zaposlen;

class ZaposlenObserver
{
    public function creating(Zaposlen $zaposlen): void
    {
        if (Putnik::where('user_id', $zaposlen->user_id)->exists()) {
            throw new \DomainException("User {$zaposlen->user_id} already exists as Putnik.");
        }
    }
}
