<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\Support\AuthHelpers;
use Tests\Support\Browser;
use Tests\Support\Hibp;

/** S-03: password >= 12 karakter dan tidak ada di daftar bocor. */
$passes = fn (string $password): bool => Validator::make(['password' => $password], ['password' => Password::default()])->passes();

it('menolak password kurang dari 12 karakter (batas tepat: 11 ditolak, 12 diterima)', function () use ($passes) {
    expect($passes('Abcdefghij1'))->toBeFalse();   // 11
    expect($passes('Abcdefghijk1'))->toBeTrue();   // 12
    expect($passes(''))->toBeFalse();
    expect($passes('pendek'))->toBeFalse();
});

it('menolak password yang ada di daftar bocor', function () use ($passes) {
    Hibp::leak('Password12345!');

    expect($passes('Password12345!'))->toBeFalse();
    expect($passes('Password12345?'))->toBeTrue(); // beda satu karakter: tidak bocor
});

it('hanya 5 karakter awal hash SHA-1 yang dikirim ke layanan cek (k-anonymity)', function () {
    $password = 'Rahasia-Sangat-Unik-9';
    Validator::make(['password' => $password], ['password' => Password::default()])->passes();

    Http::assertSent(function ($request) use ($password) {
        $hash = strtoupper(sha1($password));

        return str_ends_with($request->url(), '/range/'.substr($hash, 0, 5)) && ! str_contains($request->url(), $password) && ! str_contains($request->url(), substr($hash, 5));
    });
});

// ---- Alur reset password
function requestResetToken(User $user): string
{
    Notification::fake();
    (new Browser)->post('/forgot-password', ['email' => $user->email])->assertRedirect();

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    return (string) $token;
}

it('reset password berhasil dengan tautan sah: password baru berlaku, password lama tidak', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user();
    $token = requestResetToken($user);
    $new = 'Password-Baru-Yang-Kuat-77';

    (new Browser)->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => $new, 'password_confirmation' => $new])
        ->assertRedirect('/login');

    $fresh = $user->fresh();
    expect(Hash::check($new, $fresh->password))->toBeTrue();
    expect(Hash::check(AuthHelpers::PASSWORD, $fresh->password))->toBeFalse();
    expect($fresh->password_changed_at)->not->toBeNull();
    expect($audit->actions())->toContain('auth.password_reset');

    (new Browser('203.0.113.31'))->login($user->email, $new)->assertRedirect('/dashboard');
});

it('reset menolak password lemah, pendek, bocor, atau konfirmasi tidak cocok', function (string $password, ?string $confirmation) {
    Hibp::leak('Password12345!');
    $user = AuthHelpers::user();
    $token = requestResetToken($user);

    (new Browser)->post('/reset-password', [
        'token' => $token, 'email' => $user->email, 'password' => $password, 'password_confirmation' => $confirmation ?? $password,
    ])->assertSessionHasErrors('password');

    expect(Hash::check(AuthHelpers::PASSWORD, $user->fresh()->password))->toBeTrue(); // tidak berubah
})->with([
    'pendek (11)' => ['Abcdefghij1', null],
    'sangat pendek' => ['abc', null],
    'bocor' => ['Password12345!', null],
    'konfirmasi beda' => ['Password-Baru-Yang-Kuat-77', 'Password-Lain-Yang-Kuat-88'],
]);

it('token reset hanya bisa dipakai sekali', function () {
    $user = AuthHelpers::user();
    $token = requestResetToken($user);
    $new = 'Password-Baru-Yang-Kuat-77';
    $payload = ['token' => $token, 'email' => $user->email, 'password' => $new, 'password_confirmation' => $new];

    (new Browser)->post('/reset-password', $payload)->assertRedirect('/login');
    $second = (new Browser)->post('/reset-password', array_merge($payload, ['password' => 'Password-Ketiga-Kuat-99', 'password_confirmation' => 'Password-Ketiga-Kuat-99']));
    $second->assertSessionHasErrors('email');

    expect(Hash::check($new, $user->fresh()->password))->toBeTrue();
});

it('token reset salah atau milik email lain ditolak', function () {
    $a = AuthHelpers::user();
    $b = AuthHelpers::user();
    $tokenA = requestResetToken($a);
    $new = 'Password-Baru-Yang-Kuat-77';

    (new Browser)->post('/reset-password', ['token' => 'token-ngawur', 'email' => $a->email, 'password' => $new, 'password_confirmation' => $new])
        ->assertSessionHasErrors('email');
    (new Browser)->post('/reset-password', ['token' => $tokenA, 'email' => $b->email, 'password' => $new, 'password_confirmation' => $new])
        ->assertSessionHasErrors('email');

    expect(Hash::check(AuthHelpers::PASSWORD, $a->fresh()->password))->toBeTrue();
    expect(Hash::check(AuthHelpers::PASSWORD, $b->fresh()->password))->toBeTrue();
});

it('"lupa password" tidak membocorkan apakah email terdaftar', function () {
    Notification::fake();
    $user = AuthHelpers::user();

    $known = (new Browser('203.0.113.41'))->post('/forgot-password', ['email' => $user->email]);
    $unknown = (new Browser('203.0.113.42'))->post('/forgot-password', ['email' => 'hantu@example.test']);

    expect($known->getStatusCode())->toBe($unknown->getStatusCode());
    expect(session('status'))->toBe(__('passwords.sent'));
    $known->assertSessionHasNoErrors();
    $unknown->assertSessionHasNoErrors();
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
    Notification::assertNothingSentTo(User::factory()->make(['email' => 'hantu@example.test']));
});

it('permintaan reset berulang dalam jendela throttle tidak mengubah respons (tidak membocorkan)', function () {
    Notification::fake();
    $user = AuthHelpers::user();
    $browser = new Browser;

    $first = $browser->post('/forgot-password', ['email' => $user->email]);
    $second = $browser->post('/forgot-password', ['email' => $user->email]);

    expect($second->getStatusCode())->toBe($first->getStatusCode());
    $second->assertSessionHasNoErrors();
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

it('PasswordBroker bawaan memakai kedaluwarsa token 60 menit', function () {
    expect((int) config('auth.passwords.users.expire'))->toBeLessThanOrEqual(60);
    expect(PasswordBroker::getRepository())->not->toBeNull();
});
