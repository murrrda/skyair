<?php

namespace App\Http\Controllers;

use App\Models\Dodeljen;
use App\Models\Flight;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeProfileController extends Controller
{
    /**
     * Serbian (latin) month names, indexed 1-12, matching the design.
     *
     * @var array<int, string>
     */
    private const MONTHS = [
        1 => 'januar', 2 => 'februar', 3 => 'mart', 4 => 'april',
        5 => 'maj', 6 => 'jun', 7 => 'jul', 8 => 'avgust',
        9 => 'septembar', 10 => 'oktobar', 11 => 'novembar', 12 => 'decembar',
    ];

    public function myFlights(Request $request)
    {
        $assignments = Dodeljen::query()
            ->where('zaposlen_user_id', $request->user()->id)
            ->with([
                'uloga',
                'flight.plane',
                'flight.route.startingAirport',
                'flight.route.landingAirport',
                'flight.routeChanges',
            ])
            ->get()
            ->sortByDesc(fn (Dodeljen $a) => $a->flight->expected_takeoff)
            ->values()
            ->map(fn (Dodeljen $a) => $this->serializeAssignment($a));

        return Inertia::render('employee/my-flights', [
            'flights' => $assignments,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAssignment(Dodeljen $assignment): array
    {
        $flight = $assignment->flight;
        $takeoff = $flight->expected_takeoff;

        $start = $flight->route?->startingAirport?->city ?? '—';
        $end = $flight->route?->landingAirport?->city ?? '—';

        $status = $this->mapStatus($flight);
        $isNew = $status === 'confirmed'
            && $assignment->created_at !== null
            && $assignment->created_at->greaterThan(now()->subDays(7));

        return [
            'id' => $assignment->id,
            'route' => "{$start} → {$end}",
            'date' => sprintf(
                '%d. %s %d. · %s',
                $takeoff->day,
                self::MONTHS[$takeoff->month],
                $takeoff->year,
                $takeoff->format('H:i'),
            ),
            'aircraft' => $flight->plane?->model ?? '—',
            'registration' => (string) ($flight->plane?->reg_number ?? '—'),
            'role' => $assignment->uloga?->naziv ?? '—',
            'status' => $status,
            'notice' => $isNew ? 'Novo raspoređivanje' : null,
        ];
    }

    private function mapStatus(Flight $flight): string
    {
        if (in_array($flight->status, ['cancelled', 'otkazan'], true)) {
            return 'cancelled';
        }

        if (in_array($flight->status, ['landed', 'completed'], true)) {
            return 'completed';
        }

        if ($flight->routeChanges->isNotEmpty()) {
            return 'changed';
        }

        return 'confirmed';
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
