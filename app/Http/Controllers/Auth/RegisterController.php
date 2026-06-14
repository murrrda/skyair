<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function registerCustomer(Request $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->all());
        event(new Registered($user));
        Auth::login($user);

        return redirect()->intended('/kupac/pretraga-letova');
    }

    public function registerEmployee(Request $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->all());
        event(new Registered($user));
        Auth::login($user);

        return redirect()->intended(config('fortify.home'));
    }
}
