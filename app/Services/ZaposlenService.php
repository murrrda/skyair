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

    /**
     * Keep the role-specific `dispatchers` table in sync with the employee's
     * role. A flight's dispatcher_id is a FK to `dispatchers`, so a dispatcher
     * must have a row there before they can create flights.
     */
    public function syncDispatcherRecord(Zaposlen $zaposlen): void
    {
        $isDispatcher = $zaposlen->role === 'dispatcher';
        $exists = DB::table('dispatchers')->where('user_id', $zaposlen->user_id)->exists();

        if ($isDispatcher && ! $exists) {
            DB::table('dispatchers')->insert([
                'user_id' => $zaposlen->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        // Role moved away from dispatcher — drop the row, but only when nothing
        // still references it, otherwise the delete would violate a FK.
        if (! $isDispatcher && $exists && ! $this->dispatcherIsReferenced($zaposlen->user_id)) {
            DB::table('dispatchers')->where('user_id', $zaposlen->user_id)->delete();
        }
    }

    private function dispatcherIsReferenced(int $userId): bool
    {
        foreach (['flights', 'route_changes', 'plane_changes'] as $table) {
            if (DB::table($table)->where('dispatcher_id', $userId)->exists()) {
                return true;
            }
        }

        return false;
    }
}
