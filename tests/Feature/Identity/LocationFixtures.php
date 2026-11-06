<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Domain\Identity\Models\Location;
use App\Domain\Identity\Models\User;
use App\Support\Database\LocationGuard;
use App\Support\LocationContext;
use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\SampleLocatedLine;

/**
 * Helper bersama test scope lokasi: tabel contoh + data dua lokasi.
 *
 * @phpstan-type TwoLocations array{a: Location, b: Location, userA: User, userB: User, lineA: SampleLocatedLine, lineB: SampleLocatedLine}
 */
final class LocationFixtures
{
    /**
     * Dipanggil di awal tiap test. Tabel dibuat di dalam transaksi RefreshDatabase, jadi DDL ikut dibatalkan.
     *
     * @return TwoLocations
     */
    public static function boot(): array
    {
        self::createTable();

        return self::twoLocations();
    }

    public static function createTable(): void
    {
        Schema::create('sample_located_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->timestampTz('occurred_at')->nullable();
            $table->timestampsTz();
        });
        // Indeks unik wajib menyertakan location_id agar upsert aman (S1, ADR-003).
        Schema::table('sample_located_lines', fn (Blueprint $t) => $t->unique(['location_id', 'name']));
        LocationGuard::attach('sample_located_lines'); // lapis terakhir di database (F4)
    }

    /** @return TwoLocations */
    public static function twoLocations(): array
    {
        $a = Location::factory()->create(['code' => 'LOCA', 'timezone' => 'Asia/Jakarta']);
        $b = Location::factory()->create(['code' => 'LOCB', 'timezone' => 'Asia/Makassar']);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userA->locations()->attach($a);
        $userB->locations()->attach($b);

        $lineA = self::seedLine(['location_id' => $a->id, 'name' => 'A-1']);
        $lineB = self::seedLine(['location_id' => $b->id, 'name' => 'B-1']);

        return ['a' => $a, 'b' => $b, 'userA' => $userA, 'userB' => $userB, 'lineA' => $lineA, 'lineB' => $lineB];
    }

    /**
     * Seeder/proses sistem: menulis location_id eksplisit hanya lewat konteks sistem yang disengaja (F3).
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function seedLine(array $attributes): SampleLocatedLine
    {
        return app(LocationContext::class)->asSystem(fn () => SampleLocatedLine::withoutLocationScope()->create($attributes));
    }

    /** Jalankan $callback seolah-olah di request HTTP (bukan console). @template T @param Closure(): T $callback @return T */
    public static function asHttpRequest(Closure $callback): mixed
    {
        $property = new \ReflectionProperty(app(), 'isRunningInConsole');
        $original = $property->getValue(app());
        $property->setValue(app(), false);
        try {
            return $callback();
        } finally {
            $property->setValue(app(), $original);
        }
    }
}
