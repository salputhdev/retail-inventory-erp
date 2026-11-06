<?php

declare(strict_types=1);

namespace App\Domain\Identity\Listeners;

use Illuminate\Auth\Events\Login;

/** Menandai saat login pada sesi (dasar batas absolut 12 jam, S-04) dan mencatat last_login_at. */
final class StartSessionClock
{
    public function handle(Login $event): void
    {
        $now = now();
        session()->put(['auth.login_at' => $now->getTimestamp(), 'auth.last_activity' => $now->getTimestamp()]);

        if (method_exists($event->user, 'forceFill')) {
            $event->user->forceFill(['last_login_at' => $now])->saveQuietly();
        }
    }
}
