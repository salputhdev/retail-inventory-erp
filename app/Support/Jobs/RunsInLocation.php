<?php

declare(strict_types=1);

namespace App\Support\Jobs;

use App\Support\Contracts\HasLocationAccess;
use App\Support\Contracts\LocationAware;
use App\Support\Exceptions\LocationNotAllowed;
use App\Support\LocationContext;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware job: menjalankan job dalam konteks satu lokasi eksplisit, setelah memeriksa ulang akses pemicunya.
 * Worker tidak punya Auth::user(), jadi tanpa pemeriksaan ini lokasi apa pun yang dibawa job akan diterima (F2).
 *
 *     public function middleware(): array { return [new RunsInLocation]; }
 */
final class RunsInLocation
{
    public function handle(LocationAware $job, Closure $next): mixed
    {
        $locationId = $job->locationId();
        $actorId = $job->triggeredByUserId();

        if ($actorId !== null) {
            // Akses bisa dicabut antara dispatch dan eksekusi: periksa ulang ke sumber kebenaran.
            $actor = Auth::getProvider()->retrieveById($actorId);
            if (! $actor instanceof HasLocationAccess || ! in_array($locationId, $actor->locationIds(), true)) {
                throw LocationNotAllowed::for($locationId);
            }
        }

        // Konteks eksplisit; tanpa Auth di worker, run() tidak menambah pemeriksaan.
        return app(LocationContext::class)->run($locationId, fn () => $next($job));
    }
}
