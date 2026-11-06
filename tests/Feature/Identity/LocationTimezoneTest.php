<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Location;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Identity\LocationFixtures;

// ---- Kriteria 4 (I-01): simpan UTC, tampilkan sesuai zona waktu lokasi

it('menyimpan waktu sebagai UTC di database walau input berzona waktu lokasi', function () {
    $d = LocationFixtures::boot();
    $location = $d['a']; // Asia/Jakarta (UTC+7)
    $utc = $location->toUtc('2026-11-06 10:00:00'); // 10:00 WIB = 03:00 UTC

    $line = LocationFixtures::seedLine([
        'location_id' => $location->id, 'name' => 'tz', 'occurred_at' => $utc,
    ]);

    $stored = DB::selectOne(
        "select to_char(occurred_at at time zone 'UTC', 'YYYY-MM-DD HH24:MI:SS') as t from sample_located_lines where id = ?",
        [$line->id],
    )->t;

    expect($stored)->toBe('2026-11-06 03:00:00');
});

it('sesi database memakai UTC', function () {
    $d = LocationFixtures::boot();
    expect(DB::selectOne('show timezone')->TimeZone)->toBe('UTC');
});

it('menampilkan waktu sesuai zona waktu lokasi', function () {
    $d = LocationFixtures::boot();
    $utc = '2026-11-06 03:00:00';

    expect($d['a']->format($utc, 'Y-m-d H:i'))->toBe('2026-11-06 10:00');   // Asia/Jakarta UTC+7
    expect($d['b']->format($utc, 'Y-m-d H:i'))->toBe('2026-11-06 11:00');   // Asia/Makassar UTC+8
    expect($d['a']->localize($utc)->timezoneName)->toBe('Asia/Jakarta');
});

it('menangani batas tanggal: dini hari lokal = hari sebelumnya di UTC', function () {
    $d = LocationFixtures::boot();
    $location = Location::factory()->create(['timezone' => 'Asia/Jakarta']);
    $utc = $location->toUtc('2026-11-06 01:30:00'); // 01:30 WIB = 18:30 UTC hari sebelumnya

    expect($utc->format('Y-m-d H:i'))->toBe('2026-11-05 18:30');
    expect($location->format($utc, 'Y-m-d H:i'))->toBe('2026-11-06 01:30');
});

it('pulang-pergi UTC <-> lokal tidak mengubah instan waktu', function () {
    $d = LocationFixtures::boot();
    $location = $d['b'];
    $original = CarbonImmutable::parse('2026-03-29 23:59:59', 'UTC');

    $roundTrip = $location->toUtc($location->localize($original)->format('Y-m-d H:i:s'));
    expect($roundTrip->equalTo($original))->toBeTrue();
});

it('zona waktu tiap lokasi dipakai terpisah pada data yang sama', function () {
    $d = LocationFixtures::boot();
    $line = LocationFixtures::seedLine([
        'location_id' => $d['b']->id, 'name' => 'mks', 'occurred_at' => '2026-11-06 03:00:00+00',
    ]);
    $line->refresh();

    expect($d['b']->format($line->occurred_at, 'H:i'))->toBe('11:00');
});
