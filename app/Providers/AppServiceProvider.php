<?php

namespace App\Providers;

use App\Support\LocationContext;
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
        //
    }
}
