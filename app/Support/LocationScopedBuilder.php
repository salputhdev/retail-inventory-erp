<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Exceptions\LocationNotAllowed;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * Query builder untuk model BelongsToLocation: operasi tulis massal tidak memicu event model, jadi dijaga di sini (F4).
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class LocationScopedBuilder extends Builder
{
    /** @param array<int|string, mixed> $values */
    public function insert(array $values): bool
    {
        LocationWriteGuard::assertRows($values);

        return $this->query->insert($values);
    }

    /** @param array<int|string, mixed> $values */
    public function insertOrIgnore(array $values): int
    {
        LocationWriteGuard::assertRows($values);

        return $this->query->insertOrIgnore($values);
    }

    /** @param array<string, mixed> $values */
    public function insertGetId(array $values, ?string $sequence = null): int|string
    {
        LocationWriteGuard::assertRows($values);

        return $this->query->insertGetId($values, $sequence);
    }

    /**
     * @param  array<int|string, mixed>  $columns
     * @param  Closure|QueryBuilder|Builder<*>|string  $query
     */
    public function insertUsing(array $columns, Closure|QueryBuilder|Builder|string $query): int
    {
        // Baris berasal dari subquery: location_id tidak bisa diperiksa per baris.
        throw new LocationNotAllowed('insertUsing() tidak didukung pada model ber-scope lokasi; pakai insert() dengan location_id eksplisit.');
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @param  array<int|string, mixed>|string  $uniqueBy
     * @param  array<int|string, mixed>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        LocationWriteGuard::assertRows($values);
        // Kunci konflik tanpa location_id membuat ON CONFLICT DO UPDATE menimpa baris lokasi LAIN (S1).
        LocationWriteGuard::assertConflictKeyIncludesLocation($uniqueBy);
        LocationWriteGuard::assertNotChangingLocation($update ?? array_keys(is_array($values[0] ?? null) ? $values[0] : $values));

        return parent::upsert($values, $uniqueBy, $update);
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): int
    {
        LocationWriteGuard::assertNotChangingLocation($values);

        return parent::update($values);
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @param  array<int, string>  $returning
     * @param  array<int|string, string>|string|null  $uniqueBy
     * @return Collection<int, \stdClass>
     */
    public function insertOrIgnoreReturning(array $values, array $returning = ['*'], array|string|null $uniqueBy = null)
    {
        LocationWriteGuard::assertRows($values);

        return $this->query->insertOrIgnoreReturning($values, $returning, $uniqueBy);
    }

    /**
     * @param  array<int|string, mixed>  $columns
     * @param  Closure|QueryBuilder|Builder<*>|string  $query
     */
    public function insertOrIgnoreUsing(array $columns, Closure|QueryBuilder|Builder|string $query): int
    {
        // Sama seperti insertUsing: baris berasal dari subquery, location_id tidak bisa diperiksa per baris.
        throw new LocationNotAllowed('insertOrIgnoreUsing() tidak didukung pada model ber-scope lokasi; pakai insertOrIgnore() dengan location_id eksplisit.');
    }

    /**
     * Tanpa override ini updateFrom() diteruskan ke query mentah dan MELEWATI global scope (S3).
     *
     * @param  array<string, mixed>  $values
     */
    public function updateFrom(array $values): int
    {
        LocationWriteGuard::assertNotChangingLocation($values);

        return $this->toBase()->updateFrom($values); // toBase() menerapkan LocationScope
    }

    /** truncate() menghapus semua baris lintas lokasi dan melewati scope (S3). */
    public function truncate(): void
    {
        throw new LocationNotAllowed('truncate() tidak diizinkan pada model ber-scope lokasi (menghapus data semua lokasi).');
    }

    /**
     * Deny-by-default: metode tulis yang belum dijaga eksplisit di kelas ini TIDAK diteruskan ke query mentah
     * (yang melewati global scope). Mencegah metode tulis baru dari Laravel bocor diam-diam. Lihat
     * LocationScopedBuilderCoverageTest yang gagal bila ada metode tulis Query\Builder yang belum tercakup.
     *
     * @param  string  $method
     * @param  array<int|string, mixed>  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        if (preg_match('/^(insert|update|upsert|delete|truncate|increment|decrement)/i', $method) === 1 && ! static::hasGlobalMacro($method) && ! $this->hasMacro($method) && ! $this->model->hasNamedScope($method)) {
            throw new LocationNotAllowed("Metode tulis {$method}() belum dijaga untuk model ber-scope lokasi; tambahkan override di LocationScopedBuilder.");
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>|callable  $values
     */
    public function updateOrInsert(array $attributes, array|callable $values = []): bool
    {
        // $attributes boleh memuat location_id (kondisi pencarian + nilai insert); $values (yang di-update) tidak boleh.
        $updates = is_array($values) ? $values : [];
        LocationWriteGuard::assertNotChangingLocation($updates);
        LocationWriteGuard::assertRows($attributes + $updates);

        return $this->query->updateOrInsert($attributes, $values);
    }
}
