<?php

declare(strict_types=1);

namespace App\Support\Casts;

use App\Support\Quantity;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast kolom numeric(14,3) <-> Quantity. Tipe tulis tidak menyertakan float, sehingga Larastan menolak
 * penugasan float ke atribut ber-cast ini.
 *
 * @implements CastsAttributes<Quantity, Quantity|string|int>
 */
final class QuantityCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Quantity
    {
        return $value === null ? null : Quantity::of((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Quantity::fromMixed($value, $key)->toString();
    }
}
