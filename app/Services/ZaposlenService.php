<?php

namespace App\Services;

use App\Models\Zaposlen;
use Illuminate\Support\Facades\DB;

class ZaposlenService
{
    public function terminate(Zaposlen $zaposlen, string $razlog, ?string $datum = null): void
    {
        $datum ??= now()->toDateString();

        DB::transaction(function () use ($zaposlen, $razlog, $datum) {
            $zaposlen->update([
                'status' => 'otkazan',
                'datum_otkaza' => $datum,
                'razlog_otkaza' => $razlog,
            ]);

            // Close any open risk periods for this employee.
            $zaposlen->periodiRizika()
                ->whereNull('datum_kraja')
                ->update(['datum_kraja' => $datum]);
        });
    }
}
