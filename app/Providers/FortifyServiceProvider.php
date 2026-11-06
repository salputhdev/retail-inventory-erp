<?php

namespace App\Providers;

use App\Domain\Identity\Actions\ResetUserPassword;
use App\Domain\Identity\Models\User;
use App\Support\Auth\FifteenMinuteLoginRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 5 gagal / 15 menit per email+IP, 20 per IP (US-1.22 AC2).
        $this->app->singleton(LoginRateLimiter::class, FifteenMinuteLoginRateLimiter::class);

        // Tidak membocorkan apakah sebuah email terdaftar lewat halaman "lupa password".
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, fn () => new class implements FailedPasswordResetLinkRequestResponse
        {
            public function toResponse($request)
            {
                return back()->with('status', __('passwords.sent'));
            }
        });
    }

    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            // Tidak ada "ingat saya": cookie jangka panjang akan melewati batas sesi absolut (S-04).
            $request->merge(['remember' => false]);

            $user = User::query()->whereRaw('lower(email) = ?', [Str::lower((string) $request->input('email'))])->first();

            // Selalu mencocokkan hash, juga untuk email yang tidak ada: waktu respons sama (anti-enumerasi).
            $valid = Hash::check((string) $request->input('password'), $user->password ?? self::dummyHash());

            return $user !== null && $valid && $user->is_active ? $user : null;
        });
    }

    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make(Str::random(40));
    }
}
