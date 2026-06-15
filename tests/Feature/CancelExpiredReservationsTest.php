<?php

use App\Models\EmailQueue;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\User;
use App\Notifications\ReservationAutoCancelled;
use Illuminate\Support\Facades\Hash;

function makeCustomer(): User
{
    return User::create([
        'name' => 'Kupac '.uniqid(),
        'email' => 'kupac_'.uniqid().'@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Kupac',
        'last_name' => 'Test',
        'date_of_birth' => '1990-01-01',
        'address' => 'Beograd',
    ]);
}

function pendingReservation(User $user, string $code, int $ageHours): Reservation
{
    $reservation = Reservation::create([
        'user_id' => $user->id,
        'total_price' => 12000,
        'code' => $code,
    ]);

    $state = ReservationState::create([
        'reservation_id' => $reservation->id,
        'status' => 'pending',
    ]);
    $reservation->update(['latest_state_id' => $state->id]);

    // Backdate creation without touching the updated_at handling.
    Reservation::where('id', $reservation->id)->update(['created_at' => now()->subHours($ageHours)]);

    return $reservation->fresh();
}

test('expired unpaid reservation is cancelled and the customer is notified', function () {
    $user = makeCustomer();
    $reservation = pendingReservation($user, 'SA-EXPIRE1', 25);

    $this->artisan('reservations:cancel-expired')->assertSuccessful();

    expect($reservation->fresh()->latestState->status)->toBe('cancelled')
        ->and($user->notifications()->where('type', ReservationAutoCancelled::class)->count())->toBe(1)
        ->and(EmailQueue::where('recipient_email', $user->email)
            ->where('subject', 'like', '%'.$reservation->code.'%')->exists())->toBeTrue();
});

test('a reservation still within the 24h window is left untouched', function () {
    $user = makeCustomer();
    $reservation = pendingReservation($user, 'SA-FRESH1', 2);

    $this->artisan('reservations:cancel-expired')->assertSuccessful();

    expect($reservation->fresh()->latestState->status)->toBe('pending')
        ->and($user->notifications()->count())->toBe(0)
        ->and(EmailQueue::where('recipient_email', $user->email)->exists())->toBeFalse();
});
