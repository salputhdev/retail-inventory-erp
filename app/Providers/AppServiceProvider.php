<?php

namespace App\Providers;

use App\Support\LocationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // scoped: di-reset setiap request dan setiap job queue, jadi konteks lokasi tidak bocor antar eksekusi.
        $this->app->scoped(LocationContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Atribut di luar $fillable melempar exception, tidak dibuang diam-diam (mass assignment tersembunyi, F5).
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
