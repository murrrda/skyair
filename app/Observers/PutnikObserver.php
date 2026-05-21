<?php

namespace App\Observers;

use App\Models\Putnik;
use App\Models\Zaposlen;

class PutnikObserver
{
    public function creating(Putnik $putnik): void
    {
        if (Zaposlen::where('user_id', $putnik->user_id)->exists()) {
            throw new \DomainException("User {$putnik->user_id} already exists as Zaposlen.");
        }
    }
}
