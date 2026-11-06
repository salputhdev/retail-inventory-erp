<?php

declare(strict_types=1);

use App\Support\Exceptions\LocationNotAllowed;
use App\Support\LocationScopedBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Identity\LocationFixtures;
use Tests\Fixtures\Models\SampleLocatedLine;

use function Pest\Laravel\actingAs;

/** Temuan review kedua T1.02: S1 (upsert), S2 (insertOrIgnoreReturning/Using), S3 (updateFrom/truncate). */

// ---- S1: upsert dengan kunci konflik tanpa location_id menimpa baris lokasi lain
it('S1: upsert dengan uniqueBy tanpa location_id ditolak (tidak menimpa baris lokasi lain)', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);

    // User A memakai id milik baris lokasi B sebagai kunci konflik, tapi location_id = A.
    $attack = fn () => SampleLocatedLine::query()->upsert(
        [['id' => $d['lineB']->id, 'name' => 'DITIMPA', 'location_id' => $d['a']->id]],
        ['id'],
        ['name'],
    );

    expect($attack)->toThrow(LocationNotAllowed::class, 'location_id pada kunci konflik');
    expect(SampleLocatedLine::withoutLocationScope()->whereKey($d['lineB']->id)->value('name'))->toBe('B-1');
});

it('S1: upsert dengan location_id pada kunci konflik hanya menyentuh lokasinya sendiri', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    $now = now();

    // Nama sama dengan baris lokasi B ("B-1"): konflik hanya terjadi di (location_id A, "A-1").
    SampleLocatedLine::query()->upsert([
        ['location_id' => $d['a']->id, 'name' => 'A-1', 'occurred_at' => '2026-11-06 01:00:00+00', 'created_at' => $now, 'updated_at' => $now],
        ['location_id' => $d['a']->id, 'name' => 'B-1', 'occurred_at' => '2026-11-06 02:00:00+00', 'created_at' => $now, 'updated_at' => $now],
    ], ['location_id', 'name'], ['occurred_at']);

    expect(SampleLocatedLine::withoutLocationScope()->where('location_id', $d['b']->id)->value('occurred_at'))->toBeNull(); // B tidak tersentuh
    expect(SampleLocatedLine::query()->count())->toBe(2); // A: "A-1" (update) + "B-1" (baris baru milik A)
});

it('S1: uniqueBy bentuk string juga diperiksa', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(fn () => SampleLocatedLine::query()->upsert([['id' => 1, 'name' => 'x', 'location_id' => $d['a']->id]], 'id', ['name']))
        ->toThrow(LocationNotAllowed::class);
});

// ---- S2: metode passthru yang tidak di-override
it('S2: insertOrIgnoreReturning ke lokasi lain atau tanpa location_id ditolak', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);

    expect(fn () => SampleLocatedLine::query()->insertOrIgnoreReturning([['location_id' => $d['b']->id, 'name' => 'x']]))
        ->toThrow(LocationNotAllowed::class);
    expect(fn () => SampleLocatedLine::query()->insertOrIgnoreReturning([['name' => 'x']]))->toThrow(LocationNotAllowed::class);
    expect(fn () => SampleLocatedLine::query()->insertOrIgnoreReturning([['location_id' => '1_0', 'name' => 'x']]))->toThrow(LocationNotAllowed::class);
    expect(SampleLocatedLine::withoutLocationScope()->count())->toBe(2);
});

it('S2: insertOrIgnoreReturning yang sah tetap bekerja di lokasi sendiri', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    $now = now();

    $rows = SampleLocatedLine::query()->insertOrIgnoreReturning(
        [['location_id' => $d['a']->id, 'name' => 'baru', 'created_at' => $now, 'updated_at' => $now]],
        ['name', 'location_id'],
    );
    expect($rows)->toHaveCount(1)->and($rows->first()->name)->toBe('baru');
});

it('S2: insertOrIgnoreUsing ditolak', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(fn () => SampleLocatedLine::query()->insertOrIgnoreUsing(['name', 'location_id'], DB::table('sample_located_lines')->select(['name', 'location_id'])))
        ->toThrow(LocationNotAllowed::class);
});

// ---- S3: metode yang jatuh ke query mentah (tanpa scope)
it('S3: updateFrom tetap ber-scope: tidak mengubah baris lokasi lain', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);

    $affected = SampleLocatedLine::query()->whereKey($d['lineB']->id)->updateFrom(['name' => 'DIUBAH']);

    expect($affected)->toBe(0);
    expect(SampleLocatedLine::withoutLocationScope()->whereKey($d['lineB']->id)->value('name'))->toBe('B-1');
});

it('S3: updateFrom tidak boleh mengubah location_id', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(fn () => SampleLocatedLine::query()->whereKey($d['lineA']->id)->updateFrom(['location_id' => $d['b']->id]))
        ->toThrow(LocationNotAllowed::class, 'tidak boleh diubah');
});

it('S3: truncate ditolak dan data semua lokasi aman', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);

    expect(fn () => SampleLocatedLine::query()->truncate())->toThrow(LocationNotAllowed::class, 'truncate()');
    expect(SampleLocatedLine::withoutLocationScope()->count())->toBe(2);
});

// ---- Deny-by-default + tidak merusak scope bernama
it('metode tulis yang belum dijaga ditolak, bukan diteruskan ke query mentah', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);

    $builder = SampleLocatedLine::query();
    expect(fn () => $builder->__call('updateMagicBaru', [['x' => 1]]))->toThrow(LocationNotAllowed::class, 'belum dijaga');
    expect(fn () => $builder->__call('insertSesuatu', [[]]))->toThrow(LocationNotAllowed::class, 'belum dijaga');
    expect(fn () => $builder->__call('deleteSemua', []))->toThrow(LocationNotAllowed::class, 'belum dijaga');
});

it('scope bernama berawalan "updated" tidak ikut terblokir', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(SampleLocatedLine::query()->updatedLike('A-1')->count())->toBe(1);
});

it('metode baca/agregat yang ada di passthru tetap bekerja dan ber-scope', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(SampleLocatedLine::query()->count())->toBe(1);
    expect(SampleLocatedLine::query()->exists())->toBeTrue();
    expect(SampleLocatedLine::query()->max('id'))->toBe($d['lineA']->id);
});

// ---- Penjaga masa depan: gagal bila Laravel menambah metode tulis yang belum tercakup
it('SEMUA metode tulis Query\\Builder tercakup (di-override atau didefinisikan Eloquent), tidak jatuh ke __call mentah', function () {
    $writePrefix = '/^(insert|update|upsert|delete|truncate|increment|decrement)/i';
    $uncovered = [];

    foreach ((new ReflectionClass(QueryBuilder::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if (preg_match($writePrefix, $method->name) === 1 && ! method_exists(LocationScopedBuilder::class, $method->name)) {
            $uncovered[] = $method->name;
        }
    }

    expect($uncovered)->toBe([], 'Metode tulis Query\\Builder belum dijaga LocationScopedBuilder: '.implode(', ', $uncovered));
});
