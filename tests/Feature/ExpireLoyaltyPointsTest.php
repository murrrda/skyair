<?php

use App\Models\LoyaltyPoint;
use App\Models\Putnik;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Hash;

function loyaltyCustomer(int $rewardBalance): User
{
    $user = User::create([
        'name' => 'Kup '.uniqid(),
        'email' => 'kup_'.uniqid().'@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Kup',
        'last_name' => 'Ac',
        'date_of_birth' => '1990-01-01',
        'address' => 'Beograd',
    ]);

    Putnik::create([
        'user_id' => $user->id,
        'credit_card_number' => '',
        'status_points' => 0,
        'reward_points' => $rewardBalance,
        'tier' => 'silver',
    ]);

    return $user;
}

function rewardLot(User $user, int $amount, CarbonInterface $expiresAt, ?CarbonInterface $expiredAt = null): LoyaltyPoint
{
    return LoyaltyPoint::create([
        'user_id' => $user->id,
        'type' => 'reward',
        'action' => 'earned',
        'amount' => $amount,
        'description' => 'test lot',
        'expires_at' => $expiresAt,
        'expired_at' => $expiredAt,
    ]);
}

test('expired reward lots are removed from the balance and recorded', function () {
    $user = loyaltyCustomer(1500);
    $expired = rewardLot($user, 1000, now()->subDays(2));  // past validity
    $valid = rewardLot($user, 500, now()->addMonths(6));   // still valid

    $this->artisan('loyalty:expire-points')->assertSuccessful();

    expect((int) Putnik::find($user->id)->reward_points)->toBe(500)
        ->and($expired->fresh()->expired_at)->not->toBeNull()
        ->and($valid->fresh()->expired_at)->toBeNull()
        ->and((int) LoyaltyPoint::where('user_id', $user->id)->where('action', 'expired')->sum('amount'))->toBe(1000);
});

test('expiry never pushes the balance below zero', function () {
    $user = loyaltyCustomer(300); // most of the lot was already spent
    rewardLot($user, 1000, now()->subDays(2));

    $this->artisan('loyalty:expire-points')->assertSuccessful();

    expect((int) Putnik::find($user->id)->reward_points)->toBe(0)
        ->and((int) LoyaltyPoint::where('user_id', $user->id)->where('action', 'expired')->sum('amount'))->toBe(300);
});

test('a second run does not re-expire already processed lots', function () {
    $user = loyaltyCustomer(1000);
    rewardLot($user, 1000, now()->subDays(2));

    $this->artisan('loyalty:expire-points')->assertSuccessful();
    $this->artisan('loyalty:expire-points')->assertSuccessful();

    expect((int) Putnik::find($user->id)->reward_points)->toBe(0)
        ->and(LoyaltyPoint::where('user_id', $user->id)->where('action', 'expired')->count())->toBe(1);
});
