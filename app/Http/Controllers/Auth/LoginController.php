<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function adminLogin(Request $request) {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Pogrešni kredencijali.']);
        }

        if (!Auth::user()->is_admin) {
            Auth::logout();
            return back()->withErrors(['email' => 'Nemate admin pristup.']);
        }

        $request->session()->regenerate();
        return redirect('/admin');
    }

    public function kupacLogin(Request $request) {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Pogrešni kredencijali.']);
        }

        if (Auth::user()->role !== 'customer') {
            Auth::logout();
            return back()->withErrors(['email' => 'Ovaj nalog nije registrovan kao kupac.']);
        }

        $request->session()->regenerate();
        return redirect('/kupac/pretraga-letova');
    }
}
