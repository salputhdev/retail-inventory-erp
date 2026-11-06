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
 *  - location_id di luar konteks/akses ditolak;
 *  - location_id tidak boleh diubah setelah dibuat.
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

        static::creating(function (Model $model): void {
            $context = app(LocationContext::class);
            $explicit = $model->getAttribute('location_id');

            if ($explicit === null) {
                $ids = $context->ids();
                if (count($ids) !== 1) {
                    throw LocationNotAllowed::ambiguous();
                }
                $model->setAttribute('location_id', $ids[0]);

                return;
            }

            // Eksplisit: bila ada konteks, harus termasuk di dalamnya. Tanpa konteks (seeder/sistem), diizinkan.
            if ($context->hasContext() && ! in_array((int) $explicit, $context->ids(), true)) {
                throw LocationNotAllowed::for((int) $explicit);
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('location_id')) {
                throw LocationNotAllowed::immutable();
            }
        });
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
