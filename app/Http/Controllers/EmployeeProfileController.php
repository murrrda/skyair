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
            'first_name'   => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'address'      => ['nullable', 'string', 'max:500'],
            'country'      => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'name'       => $validated['first_name'] . ' ' . $validated['last_name'],
            'email'      => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'address'    => $validated['address'] ?? null,
            'country'    => $validated['country'] ?? null,
        ]);

        return back()->with('success', 'Podaci su uspešno ažurirani.');
    }
}
