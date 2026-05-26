<?php

namespace App\Providers;

use App\Models\Putnik;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Zaposlen;
use App\Observers\PutnikObserver;
use App\Observers\SupportTicketObserver;
use App\Observers\ZaposlenObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerObservers();
        $this->registerGates();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function registerObservers(): void
    {
        Putnik::observe(PutnikObserver::class);
        Zaposlen::observe(ZaposlenObserver::class);
        SupportTicket::observe(SupportTicketObserver::class);
    }

    protected function registerGates(): void
    {
        Gate::define('is-putnik',   fn (User $user) => $user->putnik !== null);
        Gate::define('is-zaposlen', fn (User $user) => $user->zaposlen !== null);

        // Any authenticated user that isn't an employee is treated as a customer.
        Gate::define('is-kupac', fn (User $user) => $user->zaposlen === null);

        // Add a new Gate::define here whenever a new role string is introduced.
        Gate::define('is-admin', fn (User $user) => $user->zaposlen?->role === 'admin');
        Gate::define('is-pilot', fn (User $user) => $user->zaposlen?->role === 'pilot');
        Gate::define('is-agent', fn (User $user) => in_array($user->zaposlen?->role, ['agent', 'admin'], true));
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
