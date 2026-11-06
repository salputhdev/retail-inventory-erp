<?php

declare(strict_types=1);

use Tests\Support\AuthHelpers;
use Tests\Support\Browser;
use Tests\Support\Clock;

/** US-1.22 AC2 / S-03: 5 gagal per 15 menit mengunci login sementara, tercatat di audit. */
function failLogin(Browser $browser, string $email, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        $browser->post('/login', ['email' => $email, 'password' => 'salah-terus-menerus']);
    }
}

it('5 kali gagal mengunci login, bahkan untuk password yang benar', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user();
    $browser = new Browser;

    failLogin($browser, $user->email, 5);
    expect($audit->count('auth.login_failed'))->toBe(5)->and($audit->count('auth.lockout'))->toBe(0);

    $response = $browser->post('/login', ['email' => $user->email, 'password' => AuthHelpers::PASSWORD]);
    $response->assertRedirect()->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Terlalu banyak percobaan');
    $browser->get('/dashboard')->assertRedirect('/login'); // tidak ada sesi terbentuk

    expect($audit->count('auth.lockout'))->toBe(1);
    expect($audit->last('auth.lockout')['context']['email'])->toBe($user->email);
});

it('4 kali gagal belum mengunci', function () {
    $user = AuthHelpers::user();
    $browser = new Browser;

    failLogin($browser, $user->email, 4);
    $browser->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('kunci berlaku 15 menit sejak kegagalan pertama, bukan 1 menit seperti bawaan Fortify', function () {
    $user = AuthHelpers::user();
    $browser = new Browser;
    $start = now();

    failLogin($browser, $user->email, 5);

    Clock::at($start->copy()->addMinutes(14)->addSeconds(30));
    $browser->post('/login', ['email' => $user->email, 'password' => AuthHelpers::PASSWORD])->assertSessionHasErrors('email');
    $browser->get('/dashboard')->assertRedirect('/login');

    Clock::at($start->copy()->addMinutes(15)->addSeconds(5));
    $browser->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('login sukses menghapus hitungan kegagalan akun', function () {
    $user = AuthHelpers::user();
    $browser = new Browser;

    failLogin($browser, $user->email, 4);
    $browser->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
    $browser->post('/logout');

    failLogin($browser, $user->email, 4); // mulai dari nol lagi, belum terkunci
    $browser->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('email dihitung tanpa membedakan huruf besar/kecil', function () {
    $user = AuthHelpers::user(['email' => 'siti@example.test']);
    $browser = new Browser;

    failLogin($browser, 'SITI@example.test', 3);
    failLogin($browser, 'siti@EXAMPLE.test', 2);

    $browser->post('/login', ['email' => 'siti@example.test', 'password' => AuthHelpers::PASSWORD])->assertSessionHasErrors('email');
    $browser->get('/dashboard')->assertRedirect('/login');
});

it('menyerang satu akun dari IP lain tidak mengunci pemilik akun (bucket email+IP)', function () {
    $user = AuthHelpers::user();
    failLogin(new Browser('198.51.100.7'), $user->email, 6); // penyerang

    $owner = new Browser('203.0.113.99');
    $owner->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('akun lain di IP yang sama tidak ikut terkunci oleh batas akun', function () {
    $a = AuthHelpers::user();
    $b = AuthHelpers::user();

    failLogin(new Browser('203.0.113.5'), $a->email, 6);
    (new Browser('203.0.113.5'))->login($b->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('batas per IP: 20 kegagalan dengan email berbeda-beda mengunci IP itu (credential stuffing)', function () {
    $victim = AuthHelpers::user();
    $audit = AuthHelpers::fakeAudit();
    $attacker = new Browser('198.51.100.20');

    for ($i = 0; $i < 20; $i++) {
        $attacker->post('/login', ['email' => "orang{$i}@example.test", 'password' => 'tebakan-password']);
    }

    $attacker->post('/login', ['email' => $victim->email, 'password' => AuthHelpers::PASSWORD])->assertSessionHasErrors('email');
    $attacker->get('/dashboard')->assertRedirect('/login');
    expect($audit->count('auth.lockout'))->toBe(1);

    // IP lain tidak terpengaruh.
    (new Browser('203.0.113.77'))->login($victim->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('batas per IP juga berakhir setelah 15 menit', function () {
    $victim = AuthHelpers::user();
    $attacker = new Browser('198.51.100.21');
    $start = now();

    for ($i = 0; $i < 20; $i++) {
        $attacker->post('/login', ['email' => "x{$i}@example.test", 'password' => 'tebakan-password']);
    }
    Clock::at($start->copy()->addMinutes(15)->addSeconds(5));

    $attacker->login($victim->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});
