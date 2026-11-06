<?php

declare(strict_types=1);

namespace App\Support\Jobs;

use App\Support\Contracts\LocationAware;
use App\Support\LocationContext;
use Closure;

/**
 * Middleware job: menjalankan job dalam konteks satu lokasi eksplisit. Pasang di job yang mengimplementasi LocationAware:
 *
 *     public function middleware(): array { return [new RunsInLocation]; }
 */
final class RunsInLocation
{
    public function handle(LocationAware $job, Closure $next): mixed
    {
        return app(LocationContext::class)->run($job->locationId(), fn () => $next($job));
    }
}
