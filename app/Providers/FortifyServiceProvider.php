<?php

namespace App\Providers;

use App\Domain\Identity\Actions\ResetUserPassword;
use App\Domain\Identity\Models\User;
use App\Support\Audit\AuditTrail;
use App\Support\Auth\DummyHash;
use App\Support\Auth\FifteenMinuteLoginRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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

        // Tidak membocorkan apakah email terdaftar: respons "gagal" IDENTIK dengan sukses (status, isi, dan bentuk
        // JSON/HTML). Bentuk JSON sengaja sama: bawaan sukses = 200 JSON, sedangkan gagal = redirect/422.
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, fn () => new class implements FailedPasswordResetLinkRequestResponse
        {
            public function toResponse($request)
            {
                return $request->wantsJson()
                    ? new JsonResponse(['message' => trans('passwords.sent')], 200)
                    : back()->with('status', trans('passwords.sent'));
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
            $valid = Hash::check((string) $request->input('password'), $user->password ?? DummyHash::value());

            return $user !== null && $valid && $user->is_active ? $user : null;
        });

        // F4: /user/confirm-password selalu terdaftar oleh Fortify dan tidak punya batas. Tanpa ini, pemegang sesi
        // (mis. PC toko yang ditinggal login) bisa menebak password pemilik tanpa batas dan tanpa jejak audit.
        Fortify::confirmPasswordsUsing(function (User $user, ?string $password): bool {
            $key = 'confirm-password|'.$user->getAuthIdentifier();
            $audit = app(AuditTrail::class);
            $actorId = (int) $user->getAuthIdentifier();

            if (RateLimiter::tooManyAttempts($key, FifteenMinuteLoginRateLimiter::MAX_PER_ACCOUNT)) {
                $audit->record('auth.lockout', ['via' => 'confirm_password'], $actorId);

                return false;
            }

            if (Hash::check((string) $password, $user->password)) {
                RateLimiter::clear($key);

                return true;
            }

            RateLimiter::hit($key, FifteenMinuteLoginRateLimiter::WINDOW_SECONDS);
            $audit->record('auth.confirm_password_failed', [], $actorId);

            return false;
        });

        // Batas umum POST pada rute Fortify (login, lupa/reset password, kirim ulang verifikasi, konfirmasi password):
        // 60 per 15 menit per IP. GET (menampilkan halaman) tidak dihitung.
        RateLimiter::for('fortify-post', fn (Request $request) => $request->isMethod('POST')
            ? Limit::perMinutes(15, 60)->by('fortify-post|'.$request->ip())
            : Limit::none());
    }
}
