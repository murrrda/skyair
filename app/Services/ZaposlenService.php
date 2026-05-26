<?php

namespace App\Services;

use App\Models\Zaposlen;
use Illuminate\Support\Facades\DB;

class ZaposlenService
{
    public function terminate(Zaposlen $zaposlen, string $razlog, ?string $napomena = null): void
    {
        DB::transaction(function () use ($zaposlen, $razlog, $napomena) {
            $zaposlen->update([
                'status' => 'otkazan',
                'datum_otkaza' => now()->toDateString(),
                'razlog_otkaza' => $razlog,
                'napomena_otkaza' => $napomena,
            ]);

            $zaposlen->periodiRizika()
                ->whereNull('datum_kraja')
                ->update(['datum_kraja' => now()->toDateString()]);
        });
    }
}
