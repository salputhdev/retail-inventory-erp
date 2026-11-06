<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Support\AuthHelpers;
use Tests\Support\Browser;

function verificationUrl(object $user, ?DateTimeInterface $expires = null, ?string $hash = null): string
{
    return URL::temporarySignedRoute('verification.verify', $expires ?? now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => $hash ?? sha1($user->getEmailForVerification()),
    ]);
}

it('tautan verifikasi yang sah memverifikasi email dan masuk ke dashboard', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user(['email_verified_at' => null]);
    $browser = new Browser;
    $browser->login($user->email, AuthHelpers::PASSWORD);
    $browser->get('/dashboard')->assertRedirect('/email/verify');

    $browser->get(verificationUrl($user))->assertRedirect('/dashboard');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect($audit->actions())->toContain('auth.email_verified');
    $browser->get('/dashboard')->assertOk();
});

it('tautan yang diubah (hash/id), kedaluwarsa, atau tanpa tanda tangan ditolak', function (string $case) {
    $user = AuthHelpers::user(['email_verified_at' => null]);
    $other = AuthHelpers::user(['email_verified_at' => null]);
    $browser = new Browser;
    $browser->login($user->email, AuthHelpers::PASSWORD);

    $url = match ($case) {
        'hash salah' => verificationUrl($user, null, sha1('lain@example.test')),
        'milik user lain' => verificationUrl($other),
        'kedaluwarsa' => verificationUrl($user, now()->subMinute()),
        'tanpa tanda tangan' => route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]),
        default => throw new InvalidArgumentException($case),
    };

    $browser->get($url)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    expect($other->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['hash salah', 'milik user lain', 'kedaluwarsa', 'tanpa tanda tangan']);

it('tautan verifikasi memerlukan login', function () {
    $user = AuthHelpers::user(['email_verified_at' => null]);

    (new Browser)->get(verificationUrl($user))->assertRedirect('/login');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('kirim ulang tautan verifikasi memakai notifikasi dan dibatasi laju', function () {
    Notification::fake();
    $user = AuthHelpers::user(['email_verified_at' => null]);
    $browser = new Browser;
    $browser->login($user->email, AuthHelpers::PASSWORD);

    $browser->post('/email/verification-notification')->assertRedirect();
    Notification::assertSentTo($user, VerifyEmail::class);

    for ($i = 0; $i < 10; $i++) {
        $last = $browser->post('/email/verification-notification');
    }
    expect($last->getStatusCode())->toBe(429);
});
