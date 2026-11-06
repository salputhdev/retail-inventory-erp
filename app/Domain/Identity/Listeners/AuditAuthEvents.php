<?php

declare(strict_types=1);

namespace App\Domain\Identity\Listeners;

use App\Support\Audit\AuditTrail;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

/**
 * Mencatat kejadian autentikasi ke audit trail (US-1.22 AC2, US-1.25). Tidak pernah mencatat password.
 * Email yang dicoba dicatat (huruf kecil, dipotong) agar serangan terhadap akun tertentu terlihat.
 */
final class AuditAuthEvents
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, $this->login(...));
        $events->listen(Logout::class, $this->logout(...));
        $events->listen(Failed::class, $this->failed(...));
        $events->listen(Lockout::class, $this->lockout(...));
        $events->listen(PasswordReset::class, $this->passwordReset(...));
        $events->listen(Verified::class, $this->verified(...));
    }

    public function login(Login $event): void
    {
        $this->audit()->record('auth.login', [], $this->id($event->user));
    }

    public function logout(Logout $event): void
    {
        $this->audit()->record('auth.logout', [], $this->id($event->user));
    }

    public function failed(Failed $event): void
    {
        $this->audit()->record('auth.login_failed', [
            'email' => $this->email($event->credentials['email'] ?? null),
        ]);
    }

    public function lockout(Lockout $event): void
    {
        $this->audit()->record('auth.lockout', [
            'email' => $this->email($event->request->input('email')),
        ]);
    }

    public function passwordReset(PasswordReset $event): void
    {
        $this->audit()->record('auth.password_reset', [], $this->id($event->user));
    }

    public function verified(Verified $event): void
    {
        $this->audit()->record('auth.email_verified', [], $this->id($event->user));
    }

    /** Lazy: AuditTrail bisa diganti (mis. T1.07, atau fake di test) setelah subscriber terdaftar. */
    private function audit(): AuditTrail
    {
        return app(AuditTrail::class);
    }

    private function id(mixed $user): ?int
    {
        return is_object($user) && method_exists($user, 'getAuthIdentifier') ? (int) $user->getAuthIdentifier() : null;
    }

    private function email(mixed $value): ?string
    {
        return is_string($value) ? Str::limit(Str::lower(trim($value)), 190, '') : null;
    }
}
