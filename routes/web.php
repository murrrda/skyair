<?php

use App\Http\Controllers\EmployeeSupportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaneController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('/kupac/login', 'auth/kupac-login', [
    'canResetPassword' => true,
])->name('kupac.login');

Route::inertia('/kupac/register', 'auth/kupac-register', [
    'passwordRules' => '',
])->name('kupac.register');

Route::post('/kupac/register', [RegisterController::class, 'registerCustomer'])->name('kupac.register.store');
Route::post('/zaposleni/register', [RegisterController::class, 'registerEmployee'])->name('zaposleni.register.store');

Route::inertia('/admin/login', 'auth/admin-login')->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'adminLogin']);

Route::middleware(['auth', 'can:is-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/', 'admin/index')->name('index');
    Route::get('/flota', [PlaneController::class, 'index'])->name('flota.index');
    Route::get('/flota/novi', [PlaneController::class, 'create'])->name('flota.create');
    Route::post('/flota', [PlaneController::class, 'store'])->name('flota.store');
    Route::get('/flota/{plane}/uredi', [PlaneController::class, 'edit'])->name('flota.edit');
    Route::patch('/flota/{plane}', [PlaneController::class, 'update'])->name('flota.update');
    Route::delete('/flota/{plane}', [PlaneController::class, 'destroy'])->name('flota.destroy');
});
Route::post('/kupac/login', [LoginController::class, 'kupacLogin'])->name('kupac.login.store');

Route::inertia('/kupac/pretraga-letova', 'kupac/pretraga-letova')->name('kupac.pretraga-letova');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::post('/support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');

    Route::prefix('zaposleni/podrska')->name('zaposleni.podrska.')->group(function () {
        Route::get('/', [EmployeeSupportController::class, 'index'])->name('index');
        Route::post('/{ticket}/take', [EmployeeSupportController::class, 'takeOver'])->name('take');
        Route::post('/{ticket}/request-info', [EmployeeSupportController::class, 'requestInfo'])->name('requestInfo');
        Route::post('/{ticket}/transfer', [EmployeeSupportController::class, 'transfer'])->name('transfer');
        Route::post('/{ticket}/complete', [EmployeeSupportController::class, 'complete'])->name('complete');
    });

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

require __DIR__.'/settings.php';
