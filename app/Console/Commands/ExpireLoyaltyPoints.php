<?php

namespace App\Console\Commands;

use App\Models\LoyaltyPoint;
use App\Models\Putnik;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('loyalty:expire-points')]
#[Description('Expiruje reward poene kojima je istekao rok važenja i umanjuje saldo')]
class ExpireLoyaltyPoints extends Command
{
    public function handle(): int
    {
        // Earned reward lots whose validity has fully passed and which have not
        // been processed yet.
        $lotsByUser = LoyaltyPoint::query()
            ->where('type', 'reward')
            ->where('action', 'earned')
            ->whereNull('expired_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->startOfDay())
            ->get()
            ->groupBy('user_id');

        $totalExpired = 0;
        $usersAffected = 0;

        foreach ($lotsByUser as $userId => $lots) {
            DB::transaction(function () use ($userId, $lots, &$totalExpired, &$usersAffected) {
                $putnik = Putnik::find($userId);
                $balance = (int) ($putnik?->reward_points ?? 0);

                // Never remove more than the customer currently holds — points
                // already spent must not push the balance negative.
                $toRemove = min((int) $lots->sum('amount'), $balance);

                if ($putnik && $toRemove > 0) {
                    $putnik->decrement('reward_points', $toRemove);

                    LoyaltyPoint::create([
                        'user_id' => $userId,
                        'type' => 'reward',
                        'action' => 'expired',
                        'amount' => $toRemove,
                        'description' => 'Istekli reward poeni',
                    ]);

                    $totalExpired += $toRemove;
                    $usersAffected++;
                }

                // Mark every processed lot, even when nothing was removed, so it
                // is not re-evaluated on the next run.
                LoyaltyPoint::whereIn('id', $lots->pluck('id'))->update(['expired_at' => now()]);
            });
        }

        $this->info("Expirirano {$totalExpired} reward poena za {$usersAffected} korisnik(a).");

        return self::SUCCESS;
    }
}
