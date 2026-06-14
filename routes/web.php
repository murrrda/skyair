<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DispatcherFlightController;
use App\Http\Controllers\DispatcherOperationsController;
use App\Http\Controllers\EmployeeProfileController;
use App\Http\Controllers\EmployeeSupportController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\FlightTemplateController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaneController;
use App\Http\Controllers\PutnikController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\ZaposlenController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware('guest')->group(function () {
    Route::inertia('/kupac/login', 'auth/kupac-login', [
        'canResetPassword' => true,
    ])->name('kupac.login');

    Route::inertia('/kupac/register', 'auth/kupac-register', [
        'passwordRules' => '',
    ])->name('kupac.register');
});

Route::post('/kupac/register', [RegisterController::class, 'registerCustomer'])->name('kupac.register.store');
Route::post('/zaposleni/register', [RegisterController::class, 'registerEmployee'])->name('zaposleni.register.store');

Route::inertia('/admin/login', 'auth/admin-login')->name('admin.login');

Route::inertia('/employee/login', 'auth/employee-login')->name('employee.login');
Route::post('/employee/login', [LoginController::class, 'employeeLogin'])->name('employee.login.store');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('employee', ZaposlenController::class);
});

Route::post('/admin/login', [LoginController::class, 'adminLogin']);

Route::middleware(['auth', 'can:is-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/', 'admin/index')->name('index');
    Route::get('/flota', [PlaneController::class, 'index'])->name('flota.index');
    Route::get('/flota/novi', [PlaneController::class, 'create'])->name('flota.create');
    Route::post('/flota', [PlaneController::class, 'store'])->name('flota.store');
    Route::get('/flota/{plane}/uredi', [PlaneController::class, 'edit'])->name('flota.edit');
    Route::patch('/flota/{plane}', [PlaneController::class, 'update'])->name('flota.update');
    Route::delete('/flota/{plane}', [PlaneController::class, 'destroy'])->name('flota.destroy');

    Route::get('/rute', [RouteController::class, 'index'])->name('rute.index');
    Route::get('/rute/nova', [RouteController::class, 'create'])->name('rute.create');
    Route::post('/rute', [RouteController::class, 'store'])->name('rute.store');
    Route::get('/rute/{route}/uredi', [RouteController::class, 'edit'])->name('rute.edit');
    Route::patch('/rute/{route}', [RouteController::class, 'update'])->name('rute.update');
    Route::delete('/rute/{route}', [RouteController::class, 'destroy'])->name('rute.destroy');

    Route::get('/kategorije', [CategoryController::class, 'index'])->name('kategorije.index');

    Route::get('/flota/{plane}/servisi', [ServiceController::class, 'planeIndex'])->name('flota.servisi.index');
    Route::get('/flota/{plane}/servisi/novi', [ServiceController::class, 'planeCreate'])->name('flota.servisi.create');
    Route::post('/flota/{plane}/servisi', [ServiceController::class, 'planeStore'])->name('flota.servisi.store');
    Route::get('/servisi/{service}', [ServiceController::class, 'show'])->name('servisi.show');
    Route::post('/servisi/{service}/zavrsi', [ServiceController::class, 'complete'])->name('servisi.complete');
});
Route::post('/kupac/login', [LoginController::class, 'kupacLogin'])->name('kupac.login.store');

Route::inertia('/kupac/pretraga-letova', 'kupac/pretraga-letova')->name('kupac.pretraga-letova');

Route::get('/kupac/rezultati-pretrage', [FlightController::class, 'index'])->name('kupac.rezultati-pretrage');
Route::get('/kupac/detalji-leta/{flight}', [FlightController::class, 'show'])->name('kupac.detalji-leta');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::middleware(['can:is-kupac'])->group(function () {
        Route::get('/kupac/moji-letovi', [ReservationController::class, 'index'])->name('kupac.moji-letovi');
        Route::get('/kupac/placanje', [ReservationController::class, 'create'])->name('kupac.placanje');
        Route::post('/kupac/placanje', [ReservationController::class, 'store'])->name('kupac.placanje.store');
        Route::post('/kupac/rezervacija/{reservation}/plati', [ReservationController::class, 'pay'])->name('kupac.rezervacija.plati');
        Route::get('/kupac/detalji-rezervacije/{reservation}', [ReservationController::class, 'details'])->name('kupac.detalji-rezervacije');
        Route::get('/kupac/potvrda-rezervacije/{reservation}', [ReservationController::class, 'show'])->name('kupac.potvrda-rezervacije');
        Route::patch('/kupac/karta/{ticket}', [ReservationController::class, 'updateTicket'])->name('kupac.karta.update');
        Route::delete('/kupac/karta/{ticket}', [ReservationController::class, 'destroyTicket'])->name('kupac.karta.destroy');
        Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::post('/support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');
        Route::post('/support-tickets/{ticket}/rate', [SupportTicketController::class, 'rate'])->name('support-tickets.rate');
        Route::patch('/support-tickets/{ticket}/rate', [SupportTicketController::class, 'updateRating'])->name('support-tickets.rate.update');
        Route::get('/kupac/loyalty', [LoyaltyController::class, 'index'])->name('kupac.loyalty');
        Route::get('/kupac/loyalty/istorija', [LoyaltyController::class, 'history'])->name('kupac.loyalty.istorija');
        Route::get('/kupac/edit-profile', [PutnikController::class, 'editProfile'])->name('kupac.profil');
        Route::patch('/kupac/edit-profile', [PutnikController::class, 'updateProfile'])->name('kupac.profil.update');
    });

    Route::middleware(['can:is-agent'])->prefix('zaposleni/podrska')->name('zaposleni.podrska.')->group(function () {
        Route::get('/', [EmployeeSupportController::class, 'index'])->name('index');
        Route::post('/{ticket}/take', [EmployeeSupportController::class, 'takeOver'])->name('take');
        Route::post('/{ticket}/request-info', [EmployeeSupportController::class, 'requestInfo'])->name('requestInfo');
        Route::post('/{ticket}/transfer', [EmployeeSupportController::class, 'transfer'])->name('transfer');
        Route::post('/{ticket}/complete', [EmployeeSupportController::class, 'complete'])->name('complete');
    });

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::middleware(['can:is-dispatcher'])->prefix('dispatcher')->name('dispatcher.')->group(function () {
        Route::get('/', [DispatcherFlightController::class, 'index'])->name('index');
        Route::get('/dostupnost-aviona', [DispatcherFlightController::class, 'availability'])->name('availability');
        Route::get('/zakazivanje-leta', [DispatcherFlightController::class, 'create'])->name('flights.create');
        Route::post('/zakazivanje-leta', [DispatcherFlightController::class, 'store'])->name('flights.store');
        Route::get('/letovi/{flight}', [DispatcherFlightController::class, 'show'])->name('flights.show');
        Route::post('/letovi/{flight}/pokreni', [DispatcherFlightController::class, 'startFlight'])->name('flights.start');
        Route::post('/letovi/{flight}/zavrsi', [DispatcherFlightController::class, 'endFlight'])->name('flights.end');
        Route::get('/letovi/{flight}/izmena', [DispatcherFlightController::class, 'edit'])->name('flights.edit');
        Route::patch('/letovi/{flight}', [DispatcherFlightController::class, 'update'])->name('flights.update');
        Route::post('/suggest-plane', [DispatcherFlightController::class, 'suggestPlane'])->name('suggest-plane');

        Route::get('/letovi/{flight}/promena-rute', [DispatcherOperationsController::class, 'changeRoute'])->name('flights.change-route');
        Route::post('/letovi/{flight}/promena-rute', [DispatcherOperationsController::class, 'storeRouteChange'])->name('flights.store-route-change');
        Route::get('/letovi/{flight}/prinudno-sletanje', [DispatcherOperationsController::class, 'emergencyLanding'])->name('flights.emergency-landing');
        Route::post('/letovi/{flight}/prinudno-sletanje', [DispatcherOperationsController::class, 'storeEmergencyLanding'])->name('flights.store-emergency-landing');
        Route::get('/letovi/{flight}/zamena-aviona', [DispatcherOperationsController::class, 'replacePlane'])->name('flights.replace-plane');
        Route::post('/letovi/{flight}/zamena-aviona', [DispatcherOperationsController::class, 'storeReplacePlane'])->name('flights.store-replace-plane');

        Route::get('/sabloni', [FlightTemplateController::class, 'index'])->name('sabloni.index');
        Route::post('/sabloni', [FlightTemplateController::class, 'store'])->name('sabloni.store');
        Route::patch('/sabloni/{template}', [FlightTemplateController::class, 'update'])->name('sabloni.update');
        Route::delete('/sabloni/{template}', [FlightTemplateController::class, 'destroy'])->name('sabloni.destroy');
    });

    Route::middleware(['can:is-zaposlen'])->prefix('employee')->name('employee.')->group(function () {
        Route::get('/my-flights', [EmployeeProfileController::class, 'myFlights'])->name('my-flights');
        Route::get('/profile', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
    });
});

require __DIR__.'/settings.php';
