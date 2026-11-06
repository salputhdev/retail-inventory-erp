<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter;

/**
 * Pembatas login: 5 percobaan GAGAL per email+IP dalam 15 menit (S-03, US-1.22 AC2); bawaan Fortify hanya 1 menit.
 * Ditambah batas per IP (20 gagal / 15 menit) agar penyerang tidak bisa berganti-ganti email dari satu IP.
 * Hanya yang gagal dihitung; login sukses menghapus hitungan email+IP (bukan hitungan IP).
 */
final class FifteenMinuteLoginRateLimiter extends LoginRateLimiter
{
    public const WINDOW_SECONDS = 900;

    public const MAX_PER_ACCOUNT = 5;

    public const MAX_PER_IP = 20;

    public function tooManyAttempts(Request $request)
    {
        return $this->limiter->tooManyAttempts($this->throttleKey($request), self::MAX_PER_ACCOUNT)
            || $this->limiter->tooManyAttempts($this->ipKey($request), self::MAX_PER_IP);
    }

    public function increment(Request $request)
    {
        $this->limiter->hit($this->throttleKey($request), self::WINDOW_SECONDS);
        $this->limiter->hit($this->ipKey($request), self::WINDOW_SECONDS);
    }

    public function availableIn(Request $request)
    {
        return max(
            $this->limiter->availableIn($this->throttleKey($request)),
            $this->limiter->availableIn($this->ipKey($request)),
        );
    }

    private function ipKey(Request $request): string
    {
        return 'login-ip|'.$request->ip();
    }
}
