<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope: membatasi query ke lokasi yang berlaku (R-01). Diterapkan di level query, bukan di tampilan.
 * Karena memakai global scope, route model binding dan relasi otomatis ikut terbatas (data lokasi lain = 404).
 *
 * @implements Scope<Model>
 */
final class LocationScope implements Scope
{
    /** @param Builder<covariant Model> $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereIn($model->qualifyColumn('location_id'), app(LocationContext::class)->ids());
    }

    /**
     * Menambah ->withoutLocationScope() pada builder (bypass eksplisit, AR-07).
     *
     * @param  Builder<Model>  $builder
     */
    public function extend(Builder $builder): void
    {
        $scope = $this; // ditangkap eksplisit: closure macro tidak boleh bergantung pada $this milik Builder
        $builder->macro('withoutLocationScope', fn (Builder $b) => $b->withoutGlobalScope($scope));
    }
}
