<?php

declare(strict_types=1);

namespace App\Support;

/** Kuantitas stok: numeric(14,3) (Architecture §3.1). */
final class Quantity extends Decimal
{
    protected const SCALE = 3;

    protected const PRECISION = 14;
}
