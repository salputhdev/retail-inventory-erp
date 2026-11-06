<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Location;
use App\Domain\Identity\Models\User;
use App\Support\Exceptions\LocationNotAllowed;
use App\Support\Exceptions\MissingLocationContext;
use App\Support\LocationContext;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Identity\LocationFixtures;
use Tests\Fixtures\Models\SampleLocatedLine;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

// ---- Kriteria 1: query otomatis terfilter ke lokasi user yang login
it('hanya menampilkan data lokasi milik user yang login', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(SampleLocatedLine::query()->pluck('name')->all())->toBe(['A-1']);

    actingAs($d['userB']);
    expect(SampleLocatedLine::query()->pluck('name')->all())->toBe(['B-1']);
});

it('user dengan dua lokasi melihat keduanya; user tanpa lokasi tidak melihat apa pun', function () {
    $d = LocationFixtures::boot();
    $multi = User::factory()->create();
    $multi->locations()->attach([$d['a']->id, $d['b']->id]);
    actingAs($multi);
    expect(SampleLocatedLine::query()->count())->toBe(2);

    $none = User::factory()->create();
    actingAs($none);
    expect(SampleLocatedLine::query()->count())->toBe(0);
});

it('scope berlaku untuk find, count, aggregate, relasi, dan query builder turunan', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    $lineB = $d['lineB'];

    expect(SampleLocatedLine::query()->find($lineB->id))->toBeNull();
    expect(SampleLocatedLine::query()->whereKey($lineB->id)->exists())->toBeFalse();
    expect(SampleLocatedLine::query()->where('name', 'B-1')->count())->toBe(0);
    expect(SampleLocatedLine::query()->orWhere('name', 'B-1')->count())->toBe(0); // orWhere tidak melonggarkan scope
    expect(SampleLocatedLine::query()->where('name', 'A-1')->orWhere('name', 'B-1')->pluck('name')->all())->toBe(['A-1']);
    expect(SampleLocatedLine::query()->count())->toBe(1);
});

it('GAGAL TERTUTUP: tanpa user dan tanpa konteks, query melempar exception', function () {
    $d = LocationFixtures::boot();
    expect(fn () => SampleLocatedLine::query()->count())->toThrow(MissingLocationContext::class);
});

it('withoutLocationScope() adalah satu-satunya bypass, dan eksplisit', function () {
    $d = LocationFixtures::boot();
    expect(SampleLocatedLine::withoutLocationScope()->count())->toBe(2);
    actingAs($d['userA']);
    // Macro Builder tidak dikenal analisis statis, jadi dipanggil lewat __call.
    expect(SampleLocatedLine::query()->__call('withoutLocationScope', [])->count())->toBe(2);
});

it('lokasi dibaca dari konteks saat terautentikasi sebagai user lain di request berikutnya (tidak ada cache lintas user)', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(SampleLocatedLine::query()->count())->toBe(1);
    actingAs($d['userB']);
    expect(SampleLocatedLine::query()->pluck('name')->all())->toBe(['B-1']);
});

// ---- Kriteria 2: route model binding -> 404
it('akses langsung ke ID lokasi lain lewat route model binding = 404', function () {
    $d = LocationFixtures::boot();
    Route::middleware('web')->get('/_test/lines/{line}', fn (SampleLocatedLine $line) => ['name' => $line->name]);

    actingAs($d['userA']);
    getJson('/_test/lines/'.$d['lineA']->id)->assertOk()->assertJson(['name' => 'A-1']);
    getJson('/_test/lines/'.$d['lineB']->id)->assertNotFound()->assertDontSee('B-1');
});

// ---- Pengaman tulis
it('mengisi location_id dari konteks bila hanya satu lokasi', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    $line = SampleLocatedLine::query()->create(['name' => 'baru']);
    expect($line->location_id)->toBe($d['a']->id);
});

it('menolak membuat data di lokasi lain', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(fn () => SampleLocatedLine::query()->create(['name' => 'x', 'location_id' => $d['b']->id]))
        ->toThrow(LocationNotAllowed::class);
});

it('menolak mengisi otomatis bila konteks memuat lebih dari satu lokasi', function () {
    $d = LocationFixtures::boot();
    $multi = User::factory()->create();
    $multi->locations()->attach([$d['a']->id, $d['b']->id]);
    actingAs($multi);
    expect(fn () => SampleLocatedLine::query()->create(['name' => 'x']))->toThrow(LocationNotAllowed::class, 'eksplisit');
    expect(SampleLocatedLine::query()->create(['name' => 'x', 'location_id' => $d['b']->id])->location_id)->toBe($d['b']->id);
});

it('location_id tidak bisa diubah setelah dibuat', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    $line = SampleLocatedLine::query()->first();
    $line->location_id = $d['b']->id;
    expect(fn () => $line->save())->toThrow(LocationNotAllowed::class, 'tidak boleh diubah');
});

it('mass assignment location_id oleh user tidak melewati pengaman', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(fn () => SampleLocatedLine::query()->create(['name' => 'x', 'location_id' => $d['b']->id]))
        ->toThrow(LocationNotAllowed::class);
});

it('seeder/sistem boleh menulis eksplisit tanpa konteks', function () {
    $d = LocationFixtures::boot();
    $line = SampleLocatedLine::withoutLocationScope()->create(['name' => 'seed', 'location_id' => $d['b']->id]);
    expect($line->location_id)->toBe($d['b']->id);
});

// ---- LocationContext eksplisit
it('LocationContext::run mempersempit ke lokasi tertentu lalu mengembalikan konteks semula', function () {
    $d = LocationFixtures::boot();
    $multi = User::factory()->create();
    $multi->locations()->attach([$d['a']->id, $d['b']->id]);
    actingAs($multi);
    $context = app(LocationContext::class);

    $names = $context->run($d['b']->id, fn () => SampleLocatedLine::query()->pluck('name')->all());
    expect($names)->toBe(['B-1']);
    expect(SampleLocatedLine::query()->count())->toBe(2); // kembali ke semua lokasi user
});

it('LocationContext::run menolak lokasi di luar akses user yang login', function () {
    $d = LocationFixtures::boot();
    actingAs($d['userA']);
    expect(fn () => app(LocationContext::class)->run($d['b']->id, fn () => 1))->toThrow(LocationNotAllowed::class);
});

it('konteks dibersihkan walau callback melempar exception', function () {
    $d = LocationFixtures::boot();
    $context = app(LocationContext::class);
    try {
        $context->run($d['a']->id, fn () => throw new RuntimeException('gagal'));
    } catch (RuntimeException) {
    }
    expect(fn () => SampleLocatedLine::query()->count())->toThrow(MissingLocationContext::class);
});

it('model Location sendiri tidak ber-scope (sumber scope)', function () {
    $d = LocationFixtures::boot();
    expect(Location::query()->count())->toBe(2);
});
