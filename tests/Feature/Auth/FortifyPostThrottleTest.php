<?php

declare(strict_types=1);

use Tests\Support\AuthHelpers;
use Tests\Support\Browser;

/** Batas umum POST pada rute Fortify: 60 per 15 menit per IP (GET tidak dihitung). */
it('POST ke rute Fortify dibatasi 60 per 15 menit per IP, IP lain tidak terpengaruh', function () {
    $user = AuthHelpers::user();
    $attacker = new Browser('198.51.100.50');

    foreach (range(1, 60) as $i) {
        $attacker->post('/forgot-password', ['email' => "orang{$i}@example.test"]);
    }
    expect($attacker->post('/forgot-password', ['email' => 'orang61@example.test'])->getStatusCode())->toBe(429);

    (new Browser('203.0.113.51'))->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
});

it('GET halaman login/lupa password tidak dihitung', function () {
    $browser = new Browser('198.51.100.51');

    foreach (range(1, 80) as $i) {
        $browser->get('/login')->assertOk();
    }
    $browser->get('/forgot-password')->assertOk();
});
