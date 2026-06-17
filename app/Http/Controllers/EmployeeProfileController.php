<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeProfileController extends Controller
{
    public function myFlights()
    {
        return Inertia::render('employee/my-flights');
    }

    public function myCertificates(Request $request)
    {
        $employee = $request->user()->zaposlen;

        $certificates = $employee->certificates()
            ->with('type:id,name')
            ->orderByDesc('expires_at')
            ->get()
            ->map(function ($certificate) {
                $expiresAt = $certificate->expires_at;
                $daysUntilExpiry = (int) now()->startOfDay()->diffInDays($expiresAt, false);

                return [
                    'id' => $certificate->id,
                    'type' => $certificate->type->name,
                    'issued_at' => $certificate->issued_at->toDateString(),
                    'expires_at' => $expiresAt->toDateString(),
                    'note' => $certificate->note,
                    'status' => $daysUntilExpiry < 0 ? 'expired' : ($daysUntilExpiry < 30 ? 'expiring' : 'valid'),
                    'days_until_expiry' => $daysUntilExpiry,
                ];
            });

        return Inertia::render('employee/certificates', [
            'certificates' => $certificates,
        ]);
    }

    public function myTrainings(Request $request)
    {
        $employee = $request->user()->zaposlen;

        $trainings = $employee->trainings()
            ->with('type:id,name')
            ->orderByDesc('finished_at')
            ->get()
            ->map(fn ($training) => [
                'id' => $training->id,
                'type' => $training->type->name,
                'started_at' => $training->started_at->toDateString(),
                'finished_at' => $training->finished_at->toDateString(),
                'note' => $training->note,
            ]);

        return Inertia::render('employee/trainings', [
            'trainings' => $trainings,
        ]);
    }

    public function edit(Request $request)
    {
        return Inertia::render('employee/profile', [
            'user' => $request->user()->only([
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'address',
                'country',
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'country' => $validated['country'] ?? null,
        ]);

        return back()->with('success', 'Podaci su uspešno ažurirani.');
    }
}
