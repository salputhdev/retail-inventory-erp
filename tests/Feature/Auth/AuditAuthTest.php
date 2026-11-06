<?php

declare(strict_types=1);

use App\Support\Audit\AuditTrail;
use App\Support\Audit\DatabaseAuditTrail;
use App\Support\Audit\Redactor;
use Tests\Support\AuthHelpers;
use Tests\Support\Browser;

it('kejadian autentikasi tercatat dan TIDAK PERNAH memuat password', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user();
    $wrong = 'Tebakan-Password-Rahasia-1';
    $browser = new Browser;

    $browser->post('/login', ['email' => $user->email, 'password' => $wrong]);
    $browser->post('/login', ['email' => 'asing@example.test', 'password' => $wrong]);
    $browser->login($user->email, AuthHelpers::PASSWORD);

    expect($audit->actions())->toBe(['auth.login_failed', 'auth.login_failed', 'auth.login']);
    $dump = json_encode($audit->records);
    expect($dump)->not->toContain($wrong)->and($dump)->not->toContain(AuthHelpers::PASSWORD);
    expect($audit->records[1]['context']['email'])->toBe('asing@example.test');
});

it('email yang dicatat dinormalisasi dan dipotong (tidak bisa membanjiri log)', function () {
    $audit = AuthHelpers::fakeAudit();
    (new Browser)->post('/login', ['email' => '  BESAR@Example.TEST  ', 'password' => 'x']);
    (new Browser('203.0.113.9'))->post('/login', ['email' => str_repeat('a', 5000).'@example.test', 'password' => 'x']);

    expect($audit->records[0]['context']['email'])->toBe('besar@example.test');
    expect(strlen($audit->records[1]['context']['email']))->toBeLessThanOrEqual(190);
});

it('Redactor menyamarkan kunci sensitif (termasuk bersarang) dan memotong string panjang', function () {
    $clean = Redactor::clean([
        'email' => 'a@example.test',
        'password' => 'rahasia',
        'new_password_confirmation' => 'rahasia',
        'nested' => ['api_token' => 'abc', 'ok' => 'boleh', 'two_factor_secret' => 'JBSW'],
        'Authorization' => 'Bearer xyz',
        'cookie' => 'laravel_session=abc',
        'panjang' => str_repeat('z', 900),
        'objek' => new stdClass,
        'angka' => 5,
    ]);

    expect($clean['email'])->toBe('a@example.test');
    expect($clean['password'])->toBe('[disamarkan]')->and($clean['new_password_confirmation'])->toBe('[disamarkan]');
    expect($clean['nested'])->toBe(['api_token' => '[disamarkan]', 'ok' => 'boleh', 'two_factor_secret' => '[disamarkan]']);
    expect($clean['Authorization'])->toBe('[disamarkan]')->and($clean['cookie'])->toBe('[disamarkan]');
    expect(strlen($clean['panjang']))->toBe(500);
    expect($clean['objek'])->toBe('[stdClass]')->and($clean['angka'])->toBe(5);
});

it('AuditTrail terikat ke DatabaseAuditTrail (T1.07)', function () {
    expect(app(AuditTrail::class))->toBeInstanceOf(DatabaseAuditTrail::class);
});

// ---- ISS-022: email yang bukan format email tidak dicatat apa adanya
it('ISS-022: email login yang bukan format email dicatat sebagai [bukan-email], bukan nilai asli', function () {
    $audit = AuthHelpers::fakeAudit();
    (new Browser)->post('/login', ['email' => 'Kata-Sandi-Tertukar-123', 'password' => 'x']);

    expect($audit->records[0]['context']['email'])->toBe('[bukan-email]');
    expect(json_encode($audit->records))->not->toContain('Kata-Sandi-Tertukar-123');
});

it('ISS-022: email login yang valid tetap dicatat apa adanya (dinormalisasi)', function () {
    $audit = AuthHelpers::fakeAudit();
    (new Browser)->post('/login', ['email' => 'Normal@Example.test', 'password' => 'x']);

    expect($audit->records[0]['context']['email'])->toBe('normal@example.test');
});
