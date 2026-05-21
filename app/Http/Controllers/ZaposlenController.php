<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\TipUgovora;
use App\Models\Zaposlen;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ZaposlenController extends Controller
{
    public function create()
    {
        Gate::authorize('is-admin');

        return Inertia::render('admin/ZaposlenCreate', [
            'tipoviUgovora' => TipUgovora::all(['id', 'naziv', 'opis']),
        ]);
    }

    public function store(Request $request, CreateNewUser $creator)
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'date_of_birth' => ['required', 'date'],
            'address' => ['required', 'string', 'max:500'],
            'role' => ['required', 'string', 'in:admin,pilot,dispatcher,agent'],
            'datum_zaposlenja' => ['required', 'date'],
            'status' => ['required', 'string', 'in:aktivan,neaktivan,otkazan'],
            'tip_ugovora_id' => ['required', 'exists:tipovi_ugovora,id'],
            'datum_potpisivanja' => ['required', 'date'],
            'datum_isteka' => ['required', 'date', 'after:datum_potpisivanja'],
            'napomena' => ['nullable', 'string'],
        ]);

        $tempPassword = Str::password(12);

        $user = $creator->create([
            ...$validated,
            'password' => $tempPassword,
            'password_confirmation' => $tempPassword,
        ]);

        event(new Registered($user));

        $zaposlen = Zaposlen::create([
            'user_id' => $user->id,
            'role' => $validated['role'],
            'datum_zaposlenja' => $validated['datum_zaposlenja'],
            'status' => $validated['status'],
        ]);

        $zaposlen->tipoviUgovora()->attach($validated['tip_ugovora_id'], [
            'datum_potpisivanja' => $validated['datum_potpisivanja'],
            'datum_isteka' => $validated['datum_isteka'],
            'napomena' => $validated['napomena'] ?? null,
        ]);

        return redirect()->route('admin.zaposleni.index')
            ->with('success', "Zaposlen {$user->first_name} {$user->last_name} uspješno registrovan. Privremena lozinka: {$tempPassword}");
    }

    public function index()
    {
        Gate::authorize('is-admin');

        $zaposleni = Zaposlen::with(['user', 'tipoviUgovora'])->paginate(20);

        return Inertia::render('admin/ZaposlenIndex', [
            'zaposleni' => $zaposleni,
        ]);
    }

    public function show(Zaposlen $zaposlen)
    {
        Gate::authorize('is-admin');

        return Inertia::render('admin/ZaposlenShow', [
            'zaposlen' => $zaposlen->load(['user', 'tipoviUgovora']),
        ]);
    }

    public function edit(Zaposlen $zaposlen)
    {
        //
    }

    public function update(Request $request, Zaposlen $zaposlen)
    {
        //
    }

    public function destroy(Zaposlen $zaposlen)
    {
        //
    }
}
