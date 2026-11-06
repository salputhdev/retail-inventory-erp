<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Tests\Support\AuthHelpers;
use Tests\Support\Browser;
use Tests\Support\Clock;

/**
 * F4: POST /user/confirm-password selalu terdaftar oleh Fortify. Pemegang sesi (mis. PC toko yang ditinggal login)
 * tidak boleh bisa menebak password pemilik tanpa batas dan tanpa jejak audit.
 */
/** @return array{0: Browser, 1: User} */
function loggedInBrowser(?string $ip = null): array
{
    $user = AuthHelpers::user();
    $browser = $ip === null ? new Browser : new Browser($ip);
    $browser->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');

    return [$browser, $user];
}

it('password benar dikonfirmasi, password salah ditolak', function () {
    [$browser] = loggedInBrowser();

    $browser->postJson('/user/confirm-password', ['password' => 'salah-sekali-123'])->assertUnprocessable();
    $browser->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertCreated();
});

it('5 tebakan salah mengunci konfirmasi, bahkan untuk password yang benar, dan tercatat di audit', function () {
    $audit = AuthHelpers::fakeAudit();
    [$browser, $user] = loggedInBrowser();

    foreach (range(1, 5) as $i) {
        $browser->postJson('/user/confirm-password', ['password' => "tebakan-{$i}-salah"])->assertUnprocessable();
    }
    expect($audit->count('auth.confirm_password_failed'))->toBe(5)->and($audit->count('auth.lockout'))->toBe(0);

    $browser->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertUnprocessable(); // terkunci
    expect($audit->count('auth.lockout'))->toBe(1);
    expect($audit->last('auth.lockout')['context']['via'])->toBe('confirm_password');
    expect($audit->last('auth.lockout')['actor'])->toBe($user->id);
    expect(json_encode($audit->records))->not->toContain('tebakan-1-salah');
});

it('kunci konfirmasi berakhir setelah 15 menit dan tidak memengaruhi user lain', function () {
    [$browser] = loggedInBrowser();
    [$other] = loggedInBrowser('203.0.113.55');
    $start = now();

    foreach (range(1, 5) as $i) {
        $browser->postJson('/user/confirm-password', ['password' => "tebakan-{$i}-salah"]);
    }
    $browser->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertUnprocessable();
    $other->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertCreated();

    Clock::at($start->copy()->addMinutes(15)->addSeconds(5));
    // sesi tetap valid secara idle? Idle 30 menit: 15 menit masih aman.
    $browser->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertCreated();
});

it('konfirmasi sukses menghapus hitungan salah', function () {
    [$browser] = loggedInBrowser();

    foreach (range(1, 4) as $i) {
        $browser->postJson('/user/confirm-password', ['password' => "tebakan-{$i}-salah"]);
    }
    $browser->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertCreated();

    foreach (range(1, 4) as $i) {
        $browser->postJson('/user/confirm-password', ['password' => "tebakan-{$i}-lagi"])->assertUnprocessable();
    }
    $browser->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertCreated(); // belum terkunci
});

it('konfirmasi password mewajibkan login', function () {
    (new Browser)->postJson('/user/confirm-password', ['password' => AuthHelpers::PASSWORD])->assertUnauthorized();
});
