<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Identity\Models\Location;
use App\Support\Exceptions\LocationNotAllowed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Untuk setiap model transaksional (punya kolom location_id, FR1.7). Memasang LocationScope dan pengaman tulis:
 *  - location_id diisi dari konteks bila tepat satu lokasi; bila lebih dari satu harus eksplisit;
 *  - location_id di luar konteks/akses ditolak, dan tanpa konteks apa pun juga ditolak (gagal tertutup);
 *  - location_id harus bilangan bulat murni (menolak '1_0', '0x0A': PostgreSQL 16+ membacanya sebagai 10);
 *  - location_id tidak boleh diubah setelah dibuat: model, query builder, dan trigger database (LocationGuard::attach).
 *
 * Batas yang diketahui: DB::table('...')->insert() mentah tidak lewat model; larang di code review.
 *
 * Bypass hanya lewat withoutLocationScope() dan wajib disebut di code review (AR-07).
 *
 * @mixin Model
 */
trait BelongsToLocation
{
    public static function bootBelongsToLocation(): void
    {
        static::addGlobalScope(new LocationScope);
    }

    /** @return LocationScopedBuilder<*> */
    public function newEloquentBuilder($query): LocationScopedBuilder
    {
        return new LocationScopedBuilder($query);
    }

    /**
     * Dijaga di performInsert (bukan event) agar saveQuietly()/withoutEvents() tidak melewatinya (F4).
     * location_id diisi dari konteks hanya bila tepat satu lokasi; selebihnya harus eksplisit dan sah (F1/F3).
     *
     * @param  Builder<static>  $query
     */
    protected function performInsert(Builder $query): bool
    {
        $this->guardLocationForInsert();

        return parent::performInsert($query);
    }

    /**
     * @param  Builder<static>  $query
     * @param  array<int|string, string>|string|null  $uniqueBy
     */
    protected function performInsertOrIgnore(Builder $query, array|string|null $uniqueBy): bool
    {
        $this->guardLocationForInsert();

        return parent::performInsertOrIgnore($query, $uniqueBy);
    }

    /** @param Builder<static> $query */
    protected function performUpdate(Builder $query): bool
    {
        if ($this->isDirty('location_id')) {
            throw LocationNotAllowed::immutable();
        }

        return parent::performUpdate($query);
    }

    private function guardLocationForInsert(): void
    {
        $explicit = $this->getAttribute('location_id');

        if ($explicit === null) {
            $context = app(LocationContext::class);
            $ids = $context->ids(); // gagal tertutup tanpa konteks
            if (count($ids) !== 1) {
                throw LocationNotAllowed::ambiguous();
            }
            $this->setAttribute('location_id', $ids[0]);

            return;
        }

        // Tulis ulang nilai yang SUDAH divalidasi, supaya yang diperiksa = yang dikirim ke database.
        $this->setAttribute('location_id', LocationWriteGuard::assertWritable($explicit));
    }

    /**
     * Bypass eksplisit LocationScope. Jangan dipakai di controller/komponen UI tanpa alasan tertulis.
     *
     * @return Builder<self>
     */
    public static function withoutLocationScope(): Builder
    {
        return static::withoutGlobalScope(LocationScope::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
