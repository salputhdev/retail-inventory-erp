<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

/**
 * Pembatas login (S-03, US-1.22 AC2): akun dikunci setelah 5 percobaan GAGAL dalam 15 menit, dari IP mana pun
 * (batas per AKUN, bukan per pasangan email+IP: penyerang yang berganti IP tidak mendapat tebakan baru).
 * Ditambah 20 gagal per IP agar penyerang tidak bisa berganti-ganti email dari satu IP.
 *
 * Konsekuensi yang diterima (ADR-013): siapa pun dapat mengunci akun korban selama 15 menit dengan 5 tebakan salah.
 * Hanya yang gagal dihitung; login sukses menghapus hitungan akun (bukan hitungan IP).
 */
final class FifteenMinuteLoginRateLimiter extends LoginRateLimiter
{
    public const WINDOW_SECONDS = 900;

    public const MAX_PER_ACCOUNT = 5;

    public const MAX_PER_IP = 20;

    public function attempts(Request $request)
    {
        return $this->limiter->attempts($this->accountKey($request));
    }

    public function tooManyAttempts(Request $request)
    {
        return $this->limiter->tooManyAttempts($this->accountKey($request), self::MAX_PER_ACCOUNT)
            || $this->limiter->tooManyAttempts($this->ipKey($request), self::MAX_PER_IP);
    }

    public function increment(Request $request)
    {
        $this->limiter->hit($this->accountKey($request), self::WINDOW_SECONDS);
        $this->limiter->hit($this->ipKey($request), self::WINDOW_SECONDS);
    }

    public function availableIn(Request $request)
    {
        return max(
            $this->limiter->availableIn($this->accountKey($request)),
            $this->limiter->availableIn($this->ipKey($request)),
        );
    }

    public function clear(Request $request)
    {
        $this->limiter->clear($this->accountKey($request));
    }

    /** Kunci per akun: email dinormalisasi lalu di-hash (panjang tetap, tidak bisa membanjiri cache dengan kunci panjang). */
    private function accountKey(Request $request): string
    {
        return 'login-acct|'.hash('sha256', Str::lower(trim((string) $request->input(Fortify::username()))));
    }

    private function ipKey(Request $request): string
    {
        return 'login-ip|'.$request->ip();
    }
}
