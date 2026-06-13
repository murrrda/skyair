<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class LoginController extends Controller
{
    public function adminLogin(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Pogrešni kredencijali.']);
        }

        if (!Gate::forUser(Auth::user())->allows('is-admin')) {
            Auth::logout();
            return back()->withErrors(['email' => 'Nemate admin pristup.']);
        }

        $request->session()->regenerate();
        return redirect('/admin');
    }

    public function employeeLogin(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Pogrešni kredencijali.']);
        }

        $user = Auth::user();

        if (!$user->isZaposlen()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Ovaj nalog nije registrovan kao zaposleni.']);
        }

        $request->session()->regenerate();

        if (Gate::forUser($user)->allows('is-dispatcher')) {
            return redirect('/dispatcher');
        }

        if (Gate::forUser($user)->any(['is-pilot', 'is-co_pilot', 'is-crew'])) {
            return redirect('/employee/my-flights');
        }

        return redirect('/employee/profile');
    }

    public function kupacLogin(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Pogrešni kredencijali.']);
        }

        if (Auth::user()->isZaposlen()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Ovaj nalog nije registrovan kao kupac.']);
        }

        $request->session()->regenerate();
        return redirect('/kupac/pretraga-letova');
    }
}
