<?php

namespace App\Providers;

use App\Domain\Identity\Listeners\AuditAuthEvents;
use App\Domain\Identity\Listeners\StartSessionClock;
use App\Support\Audit\AuditTrail;
use App\Support\Audit\LogAuditTrail;
use App\Support\Auth\PasswordRule;
use App\Support\LocationContext;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // scoped: di-reset setiap request dan setiap job queue, jadi konteks lokasi tidak bocor antar eksekusi.
        $this->app->scoped(LocationContext::class);
        $this->app->singleton(AuditTrail::class, LogAuditTrail::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Atribut di luar $fillable melempar exception, tidak dibuang diam-diam (mass assignment tersembunyi, F5).
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => PasswordRule::build());

        Event::subscribe(AuditAuthEvents::class);
        Event::listen(Login::class, StartSessionClock::class);
    }
}
