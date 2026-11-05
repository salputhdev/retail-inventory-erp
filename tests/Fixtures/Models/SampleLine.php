<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Support\Casts\MoneyCast;
use App\Support\Casts\QuantityCast;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;

/**
 * Model contoh (tanpa tabel) untuk menguji cast dan analisis statis. Model nyata tidak perlu anotasi
 * ini: tipenya diturunkan Larastan dari migrasi + cast. Tipe tulis tidak menyertakan float.
 *
 * @property-read Quantity $quantity
 * @property-write Quantity|string|int|null $quantity
 * @property-read Money $unit_cost
 * @property-write Money|string|int|null $unit_cost
 */
class SampleLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => QuantityCast::class,
            'unit_cost' => MoneyCast::class,
        ];
    }
}
