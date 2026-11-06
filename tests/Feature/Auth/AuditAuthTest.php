<?php

declare(strict_types=1);

use App\Support\Audit\AuditTrail;
use App\Support\Audit\LogAuditTrail;
use App\Support\Audit\Redactor;
use Illuminate\Support\Facades\Log;
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

it('LogAuditTrail menulis ke channel "audit" dengan konteks yang sudah disamarkan', function () {
    Log::shouldReceive('channel')->once()->with('audit')->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(function (string $action, array $context) {
        return $action === 'contoh.aksi'
            && $context['password'] === '[disamarkan]'
            && $context['actor_id'] === 7
            && array_key_exists('ip', $context);
    });

    (new LogAuditTrail)->record('contoh.aksi', ['password' => 'rahasia'], 7);
});

it('channel audit menyimpan 365 hari dan file tidak bisa dibaca semua user', function () {
    $channel = config('logging.channels.audit');

    expect($channel['max_files'])->toBeGreaterThanOrEqual(365); // L-01: retensi >= 1 tahun
    expect($channel['permission'])->toBe(0640);
    expect(app(AuditTrail::class))->toBeInstanceOf(LogAuditTrail::class);
});
