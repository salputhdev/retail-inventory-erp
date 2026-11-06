<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Support\Carbon;
use Tests\Support\AuthHelpers;
use Tests\Support\Browser;
use Tests\Support\Clock;

use function Pest\Laravel\actingAs;

/**
 * S-04 / US-1.22 AC3: idle 30 menit, absolut 12 jam. Handler sesi `array` membuang sesi setelah session.lifetime,
 * jadi dinaikkan di sini agar yang diuji adalah middleware EnforceSessionLifetime, bukan TTL driver.
 */
beforeEach(function () {
    config(['session.lifetime' => 24 * 60, 'auth.idle_timeout_minutes' => 30, 'auth.absolute_timeout_minutes' => 720]);
});

/** @return array{0: Browser, 1: User, 2: Carbon} */
function loggedIn(?User $user = null): array
{
    $user ??= AuthHelpers::user();
    $browser = new Browser;
    $browser->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');

    return [$browser, $user, now()];
}

it('idle: 29 menit 59 detik masih berlaku, tepat 30 menit berakhir', function () {
    $audit = AuthHelpers::fakeAudit();
    [$browser, , $start] = loggedIn();

    Clock::at($start->copy()->addMinutes(29)->addSeconds(59));
    $browser->get('/dashboard')->assertOk();

    Clock::at($start->copy()->addMinutes(29)->addSeconds(59)->addMinutes(30)); // 30 menit sejak aktivitas terakhir
    $browser->get('/dashboard')->assertRedirect('/login')->assertSessionHas('status', __('auth.session_expired'));
    expect($audit->last('auth.session_expired')['context']['reason'])->toBe('idle');

    $browser->get('/dashboard')->assertRedirect('/login'); // sesi sudah dicabut
});

it('aktivitas memperpanjang idle', function () {
    [$browser, , $start] = loggedIn();

    Clock::at($start->copy()->addMinutes(20));
    $browser->get('/dashboard')->assertOk();
    Clock::at($start->copy()->addMinutes(45)); // 25 menit sejak aktivitas terakhir
    $browser->get('/dashboard')->assertOk();
    Clock::at($start->copy()->addMinutes(76)); // 31 menit sejak aktivitas terakhir
    $browser->get('/dashboard')->assertRedirect('/login');
});

it('absolut: sesi yang terus aktif tetap berakhir setelah 12 jam', function () {
    $audit = AuthHelpers::fakeAudit();
    [$browser, , $start] = loggedIn();

    // aktif tiap 25 menit (< idle 30) sampai menit ke-700
    for ($minutes = 25; $minutes <= 700; $minutes += 25) {
        Clock::at($start->copy()->addMinutes($minutes));
        $browser->get('/dashboard')->assertOk();
    }

    Clock::at($start->copy()->addMinutes(719)->addSeconds(59));
    $browser->get('/dashboard')->assertOk(); // 11 jam 59 menit 59 detik: masih berlaku

    Clock::at($start->copy()->addMinutes(720)->addSeconds(1)); // idle hanya ±1 menit, tapi sudah 12 jam sejak login
    $browser->get('/dashboard')->assertRedirect('/login');
    expect($audit->last('auth.session_expired')['context']['reason'])->toBe('absolute');
});

it('request JSON pada sesi berakhir mendapat 401, bukan redirect', function () {
    [$browser, , $start] = loggedIn();
    Clock::at($start->copy()->addMinutes(31));

    $browser->getJson('/dashboard')->assertUnauthorized()->assertJson(['message' => 'Sesi berakhir.']);
});

it('user dinonaktifkan di tengah sesi: request berikutnya mencabut sesi', function () {
    $audit = AuthHelpers::fakeAudit();
    [$browser, $user] = loggedIn();
    $browser->get('/dashboard')->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $browser->get('/dashboard')->assertRedirect('/login');
    expect($audit->last('auth.session_expired')['context']['reason'])->toBe('inactive');
    $browser->get('/dashboard')->assertRedirect('/login');
});

it('password diganti setelah sesi dibuat: sesi lama dicabut, sesi baru tidak', function () {
    $audit = AuthHelpers::fakeAudit();
    [$old, $user, $start] = loggedIn();

    Clock::at($start->copy()->addMinutes(5));
    $user->forceFill(['password_changed_at' => now()])->save();

    $old->get('/dashboard')->assertRedirect('/login');
    expect($audit->last('auth.session_expired')['context']['reason'])->toBe('password_changed');

    Clock::at($start->copy()->addMinutes(6));
    $new = new Browser('203.0.113.88');
    $new->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
    $new->get('/dashboard')->assertOk();
});

it('sesi tanpa jam login (tidak lewat proses login) ditolak', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user();

    actingAs($user)->get('/dashboard')->assertRedirect('/login');
    expect($audit->last('auth.session_expired')['context']['reason'])->toBe('no_clock');
});

it('cookie sesi: HttpOnly, SameSite, dan Secure bila dikonfigurasi', function () {
    config(['session.secure' => true]);
    $response = (new Browser)->get('/login');

    $cookie = collect($response->baseResponse->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));
    expect($cookie)->not->toBeNull();
    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->isSecure())->toBeTrue();
    expect(strtolower((string) $cookie->getSameSite()))->toBe('lax');
});

it('konfigurasi batas sesi sesuai NFR S-04 di bawaan aplikasi', function () {
    // Nilai bawaan kode (bukan override test): idle 30 menit, absolut 12 jam.
    $auth = require base_path('config/auth.php');
    expect($auth['idle_timeout_minutes'])->toBe(30)->and($auth['absolute_timeout_minutes'])->toBe(720);
});
