<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function registerCustomer(Request $request, CreateNewUser $creator) {
        return $this->register($request, $creator, 'customer');
    }
    public function registerEmployee(Request $request, CreateNewUser $creator) {
        return $this->register($request, $creator, 'employee');
    }
    public function register(Request $request, CreateNewUser $creator, string $role) {
        $user = $creator->create(array_merge($request->all(), ['role' => $role]));
        event(new Registered($user));
        Auth::login($user);
        $home = $role === 'customer' ? '/kupac/pretraga-letova' : config('fortify.home');
        return redirect()->intended($home);
    }
}
