<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Audit\AuditTrail;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menegakkan umur sesi (S-04, US-1.22 AC3) di level aplikasi, bukan hanya TTL driver:
 *  - idle: tanpa aktivitas selama auth.idle_timeout_minutes (30; SESSION_LIFETIME di produksi sama, jadi TTL driver menjadi lapis kedua);
 *  - absolut: lebih dari auth.absolute_timeout_minutes (720 = 12 jam) sejak login, walau aktif terus;
 *  - user dinonaktifkan atau password diganti setelah sesi dibuat: sesi dicabut;
 *  - sesi tanpa jam (tidak melewati login ini): ditolak.
 * Tidak ada "ingat saya": cookie jangka panjang akan melewati batas absolut.
 */
final class EnforceSessionLifetime
{
    public function __construct(private readonly AuditTrail $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $now = now()->getTimestamp();
        $session = $request->session();
        $loginAt = $session->get('auth.login_at');
        $lastActivity = $session->get('auth.last_activity');
        $idleSeconds = (int) config('auth.idle_timeout_minutes') * 60;
        $absoluteSeconds = (int) config('auth.absolute_timeout_minutes') * 60;
        $passwordChangedAt = $user->password_changed_at ?? null;

        $reason = match (true) {
            ! $user->is_active => 'inactive',
            ! is_int($loginAt) => 'no_clock',
            $now - $loginAt >= $absoluteSeconds => 'absolute',
            is_int($lastActivity) && $now - $lastActivity >= $idleSeconds => 'idle',
            $passwordChangedAt !== null && $passwordChangedAt->getTimestamp() > $loginAt => 'password_changed',
            default => null,
        };

        if ($reason === null) {
            $session->put('auth.last_activity', $now);

            return $next($request);
        }

        $actorId = (int) $user->getAuthIdentifier();
        Auth::guard()->logout();
        $session->invalidate();
        $session->regenerateToken();
        $this->audit->record('auth.session_expired', ['reason' => $reason], $actorId);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sesi berakhir.'], 401);
        }

        return redirect()->route('login')->with('status', __('auth.session_expired'));
    }
}
