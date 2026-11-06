<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Validation\Rules\Password;

/**
 * Satu-satunya definisi aturan password (S-03): minimal 12 karakter dan tidak ada di daftar password bocor
 * (HaveIBeenPwned, k-anonymity: hanya 5 karakter awal hash SHA-1 yang dikirim). Dipakai oleh reset password,
 * dan nanti oleh pembuatan user (T1.08) lewat Password::default().
 *
 * Catatan: bila layanan HIBP tidak terjangkau, Laravel menganggap password aman (fail-open). Diterima untuk
 * ketersediaan; panjang minimum tetap ditegakkan.
 */
final class PasswordRule
{
    public const MIN_LENGTH = 12;

    public static function build(): Password
    {
        return Password::min(self::MIN_LENGTH)->uncompromised();
    }
}
