<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter;
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

it('F3: 5 kegagalan dari IP BERBEDA tetap mengunci akun (batas per akun, bukan email+IP)', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user();

    // Botnet: satu tebakan per IP. Dengan batas email+IP, tebakan seperti ini tidak pernah terkunci.
    foreach (['198.51.100.1', '198.51.100.2', '198.51.100.3', '198.51.100.4', '198.51.100.5'] as $ip) {
        (new Browser($ip))->post('/login', ['email' => $user->email, 'password' => 'tebakan-botnet']);
    }

    // IP baru dan password yang BENAR: akun tetap terkunci.
    $owner = new Browser('203.0.113.99');
    $owner->post('/login', ['email' => $user->email, 'password' => AuthHelpers::PASSWORD])->assertSessionHasErrors('email');
    $owner->get('/dashboard')->assertRedirect('/login');
    expect($audit->count('auth.lockout'))->toBe(1);
});

it('kunci akun berakhir setelah 15 menit dan tidak memengaruhi akun lain', function () {
    $victim = AuthHelpers::user();
    $other = AuthHelpers::user();
    $start = now();

    foreach (range(1, 5) as $i) {
        (new Browser("198.51.100.{$i}"))->post('/login', ['email' => $victim->email, 'password' => 'tebakan-botnet']);
    }

    (new Browser('203.0.113.70'))->login($other->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard'); // akun lain bebas

    $owner = new Browser('203.0.113.71');
    $owner->post('/login', ['email' => $victim->email, 'password' => AuthHelpers::PASSWORD])->assertSessionHasErrors('email');

    Clock::at($start->copy()->addMinutes(15)->addSeconds(5));
    $owner->login($victim->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('hitungan akun tidak bisa dihindari dengan variasi huruf atau spasi pada email', function () {
    $user = AuthHelpers::user(['email' => 'dewi@example.test']);

    foreach (['dewi@example.test', 'DEWI@example.test', ' dewi@example.test ', 'Dewi@Example.Test', 'dEwI@example.test'] as $i => $variant) {
        (new Browser('198.51.100.'.(10 + $i)))->post('/login', ['email' => $variant, 'password' => 'tebakan']);
    }

    (new Browser('203.0.113.80'))->post('/login', ['email' => 'dewi@example.test', 'password' => AuthHelpers::PASSWORD])->assertSessionHasErrors('email');
    expect($user->id)->toBeInt();
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

it('limiter menormalkan email SENDIRI pada kunci akun (tidak bergantung pada TrimStrings/Canonicalize)', function () {
    $limiter = app(LoginRateLimiter::class);
    $request = fn (string $email, string $ip) => tap(Request::create('/login', 'POST', ['email' => $email]), fn ($r) => $r->server->set('REMOTE_ADDR', $ip));

    foreach (['dewi@example.test', 'DEWI@EXAMPLE.TEST', '  dewi@example.test  ', 'Dewi@Example.Test'] as $i => $variant) {
        $limiter->increment($request($variant, '198.51.100.'.(100 + $i)));
    }
    expect($limiter->tooManyAttempts($request('dewi@example.test', '203.0.113.200')))->toBeFalse(); // baru 4

    $limiter->increment($request("\tDEWI@example.test", '198.51.100.110'));
    expect($limiter->tooManyAttempts($request('dewi@example.test', '203.0.113.201')))->toBeTrue(); // 5 dari 5 IP berbeda
    expect($limiter->tooManyAttempts($request('lain@example.test', '203.0.113.202')))->toBeFalse();
});
