<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('/kupac/login', 'auth/kupac-login', [
    'canResetPassword' => true,
])->name('kupac.login');

Route::inertia('/kupac/register', 'auth/kupac-register', [
    'passwordRules' => '',
])->name('kupac.register');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
