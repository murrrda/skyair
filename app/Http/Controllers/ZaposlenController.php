<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\TipUgovora;
use App\Models\User;
use App\Models\Zaposlen;
use App\Services\ZaposlenService;
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

        $request->merge([
            'gender' => $request->input('gender') ?: null,
            'jmbg' => $request->input('jmbg') ?: null,
            'country' => $request->input('country') ?: null,
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'date_of_birth' => ['required', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'jmbg' => ['nullable', 'string', 'digits:13'],
            'gender' => ['nullable', 'in:male,female'],
            'country' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:admin,pilot,dispatcher,agent,cabin_crew'],
            'datum_zaposlenja' => ['required', 'date'],
            'status' => ['required', 'string', 'in:aktivan,neaktivan,otkazan'],
            'tip_ugovora_id' => ['required', 'exists:tipovi_ugovora,id'],
            'datum_potpisivanja' => ['required', 'date'],
            'datum_isteka' => ['nullable', 'date', 'after:datum_potpisivanja'],
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

        return redirect()->route('admin.employee.index')
            ->with('success', "Zaposlen {$user->first_name} {$user->last_name} je uspješno registrovan.")
            ->with('newEmployeeCredentials', [
                'name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'password' => $tempPassword,
            ]);
    }

    public function index(Request $request)
    {
        Gate::authorize('is-admin');

        $query = Zaposlen::with(['user', 'tipoviUgovora' => fn ($q) => $q->orderByPivot('created_at', 'desc')]);

        if ($search = $request->input('search')) {
            $query->whereHas('user', fn ($q) => $q
                ->whereRaw('lower(first_name) like ?', ['%' . strtolower($search) . '%'])
                ->orWhereRaw('lower(last_name) like ?', ['%' . strtolower($search) . '%'])
                ->orWhereRaw('lower(email) like ?', ['%' . strtolower($search) . '%'])
            );
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($tipUgovoraId = $request->input('tip_ugovora_id')) {
            $query->whereHas('tipoviUgovora', fn ($q) => $q->where('tipovi_ugovora.id', $tipUgovoraId));
        }

        return Inertia::render('admin/ZaposlenIndex', [
            'zaposleni'     => $query->paginate(10)->withQueryString(),
            'tipoviUgovora' => TipUgovora::all(['id', 'naziv']),
            'filters'       => $request->only(['search', 'role', 'tip_ugovora_id']),
        ]);
    }

    public function show(Zaposlen $employee)
    {
        Gate::authorize('is-admin');

        return Inertia::render('admin/ZaposlenShow', [
            'zaposlen' => $employee->load(['user', 'tipoviUgovora']),
        ]);
    }

    public function edit(Zaposlen $employee)
    {
        Gate::authorize('is-admin');

        return Inertia::render('admin/ZaposlenEdit', [
            'zaposlen'      => $employee->load(['user', 'tipoviUgovora' => fn ($q) => $q->orderByPivot('created_at', 'desc')]),
            'tipoviUgovora' => TipUgovora::all(['id', 'naziv']),
        ]);
    }

    public function update(Request $request, Zaposlen $employee)
    {
        Gate::authorize('is-admin');

        $user = $employee->user;

        $validated = $request->validate([
            'first_name'      => ['required', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'unique:users,email,'.$user->id],
            'date_of_birth'   => ['required', 'date'],
            'address'         => ['nullable', 'string', 'max:500'],
            'phone_number'    => ['nullable', 'string', 'max:30'],
            'role'            => ['required', 'string', 'in:admin,pilot,dispatcher,agent,cabin_crew'],
            'datum_zaposlenja' => ['required', 'date'],
            'tip_ugovora_id'  => ['required', 'exists:tipovi_ugovora,id'],
            'datum_isteka'    => ['nullable', 'date'],
            'napomena'        => ['nullable', 'string'],
        ]);

        $user->update([
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'name'         => $validated['first_name'].' '.$validated['last_name'],
            'email'        => $validated['email'],
            'date_of_birth' => $validated['date_of_birth'],
            'address'      => $validated['address'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        $employee->update([
            'role'            => $validated['role'],
            'datum_zaposlenja' => $validated['datum_zaposlenja'],
        ]);

        $currentTipId = $employee->tipoviUgovora()
            ->orderByPivot('created_at', 'desc')
            ->first()
            ?->id;

        if ((int) $validated['tip_ugovora_id'] !== (int) $currentTipId) {
            $employee->tipoviUgovora()->attach($validated['tip_ugovora_id'], [
                'datum_potpisivanja' => $validated['datum_zaposlenja'],
                'datum_isteka'       => $validated['datum_isteka'] ?? null,
                'napomena'           => $validated['napomena'] ?? null,
            ]);
        } else {
            $employee->tipoviUgovora()->updateExistingPivot($validated['tip_ugovora_id'], [
                'datum_isteka' => $validated['datum_isteka'] ?? null,
                'napomena'     => $validated['napomena'] ?? null,
            ]);
        }

        return redirect()->route('admin.employee.index')
            ->with('success', 'Podaci zaposlenika su ažurirani.');
    }

    public function destroy(Request $request, Zaposlen $employee, ZaposlenService $service)
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'razlog_otkaza' => ['required', 'string', 'min:20', 'max:1000'],
            'napomena_otkaza' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($employee->status === 'otkazan') {
            return back()->withErrors(['razlog_otkaza' => 'Zaposlen je već otkazan.']);
        }

        $service->terminate($employee, $validated['razlog_otkaza'], $validated['napomena_otkaza'] ?? null);

        return redirect()->route('admin.employee.index')
            ->with('success', "Zaposlen {$employee->user->first_name} {$employee->user->last_name} je otkazan.");
    }
}
