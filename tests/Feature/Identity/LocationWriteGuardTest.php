<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Location;
use App\Domain\Identity\Models\User;
use App\Support\Exceptions\LocationNotAllowed;
use App\Support\Exceptions\MissingLocationContext;
use App\Support\LocationContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Identity\LocationFixtures;
use Tests\Fixtures\Models\SampleLocatedLine;

use function Pest\Laravel\actingAs;

/** Hardening hasil review keamanan T1.02 (F1, F3, F4). */

// ---- F1: validasi location_id ketat, nilai yang diperiksa = nilai yang disimpan
it('F1: PostgreSQL membaca "1_0" sebagai 10 (alasan validasi ketat)', function () {
    expect((int) DB::selectOne("select '1_0'::bigint as v")->v)->toBe(10);
    expect((int) '1_0')->toBe(1); // PHP membacanya 1: pemeriksaan akses dan database akan berbeda
});

it('F1: location_id berbentuk "1_0", "0x0A", spasi, desimal, atau tipe lain ditolak', function (mixed $bad) {
    $d = LocationFixtures::boot();
    $multi = User::factory()->create();
    $multi->locations()->attach($d['a']->id);
    actingAs($multi);

    expect(fn () => SampleLocatedLine::query()->create(['name' => 'x', 'location_id' => $bad]))
        ->toThrow(LocationNotAllowed::class, 'tidak valid');
    expect(SampleLocatedLine::withoutLocationScope()->count())->toBe(2); // tidak ada baris tambahan
})->with(['1_0', '0x0A', ' 1', '1 ', '1.0', '01', '-1', '0', '', '1e1', true, 1.5, [[1]]]);

it('F1: tabel angka string murni diterima dan dinormalkan ke int', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    $line = SampleLocatedLine::query()->create(['name' => 'ok', 'location_id' => (string) $d['a']->id]);
    expect($line->location_id)->toBe($d['a']->id);
});

it('F1: "1_0" tidak bisa menyelundup ke lokasi lain walau user berhak atas lokasi 1', function () {
    $d = LocationFixtures::boot();
    // Lokasi dengan id 10 dibuat; user hanya berhak atas $d['a'].
    $ten = Location::factory()->create(['id' => 10, 'code' => 'LOC10']);
    $user = User::factory()->create();
    $user->locations()->attach($d['a']->id);
    actingAs($user);

    $payload = $d['a']->id.'_0'; // mis. "5_0" => PHP (int)=5 (lolos akses), PostgreSQL=50
    expect(fn () => SampleLocatedLine::query()->create(['name' => 'attack', 'location_id' => $payload]))
        ->toThrow(LocationNotAllowed::class);
    expect($ten->id)->toBe(10);
});

// ---- F3: penulisan gagal tertutup untuk principal tak dikenal / tanpa konteks
it('F3: principal tak dikenal (bukan HasLocationAccess) ditolak', function () {
    $d = LocationFixtures::boot();
    $principal = new class extends Illuminate\Foundation\Auth\User
    {
        protected $table = 'users';
    };
    $principal->id = 999;
    Auth::setUser($principal);

    expect(fn () => app(LocationContext::class)->run($d['a']->id, fn () => 1))->toThrow(LocationNotAllowed::class, 'HasLocationAccess');
    expect(fn () => SampleLocatedLine::withoutLocationScope()->create(['name' => 'x', 'location_id' => $d['a']->id]))
        ->toThrow(MissingLocationContext::class);
});

it('F3: request HTTP guest tidak boleh memakai konteks lokasi', function () {
    $d = LocationFixtures::boot();
    expect(fn () => LocationFixtures::asHttpRequest(fn () => app(LocationContext::class)->run($d['a']->id, fn () => 1)))
        ->toThrow(LocationNotAllowed::class);
});

it('F3: console tanpa login boleh memakai run() (command/scheduler), tetapi tetap eksplisit', function () {
    $d = LocationFixtures::boot();
    expect(app(LocationContext::class)->run($d['a']->id, fn () => SampleLocatedLine::query()->count()))->toBe(1);
});

// ---- F4: location_id tidak bisa dipindah lewat jalur yang tidak memicu event
it('F4: saveQuietly dan withoutEvents tidak melewati pengaman', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);

    $line = SampleLocatedLine::query()->first();
    $line->location_id = $d['b']->id;
    expect(fn () => $line->saveQuietly())->toThrow(LocationNotAllowed::class, 'tidak boleh diubah');

    $line2 = SampleLocatedLine::query()->first();
    $line2->location_id = $d['b']->id;
    expect(fn () => SampleLocatedLine::withoutEvents(fn () => $line2->save()))->toThrow(LocationNotAllowed::class);

    // membuat baru lewat saveQuietly ke lokasi lain
    $new = new SampleLocatedLine(['name' => 'q', 'location_id' => $d['b']->id]);
    expect(fn () => $new->saveQuietly())->toThrow(LocationNotAllowed::class);
});

it('F4: update() massal yang mengubah location_id ditolak', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(fn () => SampleLocatedLine::query()->whereKey($d['lineA']->id)->update(['location_id' => $d['b']->id]))
        ->toThrow(LocationNotAllowed::class, 'tidak boleh diubah');
    // update kolom lain tetap boleh
    expect(SampleLocatedLine::query()->whereKey($d['lineA']->id)->update(['name' => 'A-1b']))->toBe(1);
});

it('F4: insert/insertOrIgnore/upsert massal ke lokasi lain atau tanpa location_id ditolak', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    $now = now();

    expect(fn () => SampleLocatedLine::query()->insert([['name' => 'x', 'location_id' => $d['b']->id, 'created_at' => $now, 'updated_at' => $now]]))
        ->toThrow(LocationNotAllowed::class);
    expect(fn () => SampleLocatedLine::query()->insert(['name' => 'x', 'created_at' => $now, 'updated_at' => $now]))
        ->toThrow(LocationNotAllowed::class);
    expect(fn () => SampleLocatedLine::query()->insertOrIgnore([['name' => 'x', 'location_id' => '1_0']]))
        ->toThrow(LocationNotAllowed::class);
    expect(fn () => SampleLocatedLine::query()->upsert([['id' => $d['lineA']->id, 'name' => 'z', 'location_id' => $d['b']->id]], ['id'], ['name']))
        ->toThrow(LocationNotAllowed::class);
    expect(fn () => SampleLocatedLine::query()->upsert([['id' => $d['lineA']->id, 'name' => 'z', 'location_id' => $d['a']->id]], ['id'], ['location_id']))
        ->toThrow(LocationNotAllowed::class, 'tidak boleh diubah');
    expect(fn () => SampleLocatedLine::query()->insertUsing(['name', 'location_id'], DB::table('sample_located_lines')->select(['name', 'location_id'])))
        ->toThrow(LocationNotAllowed::class);

    // yang sah tetap bisa
    expect(SampleLocatedLine::query()->insert([['name' => 'ok', 'location_id' => $d['a']->id, 'created_at' => $now, 'updated_at' => $now]]))->toBeTrue();
    expect(SampleLocatedLine::query()->count())->toBe(2);
});

it('F4: trigger database menolak perubahan location_id walau lewat SQL mentah', function () {
    $d = LocationFixtures::boot();

    // DB::transaction membuat savepoint: error SQL tidak membuat transaksi test berstatus "aborted" di PostgreSQL.
    $attempt = fn () => DB::transaction(fn () => DB::table('sample_located_lines')->where('id', $d['lineA']->id)->update(['location_id' => $d['b']->id]));
    expect($attempt)->toThrow(QueryException::class, 'location_id tidak boleh diubah');

    // kolom lain tetap boleh diubah; location_id tidak berubah
    DB::table('sample_located_lines')->where('id', $d['lineA']->id)->update(['name' => 'raw']);
    expect((int) DB::table('sample_located_lines')->where('id', $d['lineA']->id)->value('location_id'))->toBe($d['a']->id);
});
