<?php

declare(strict_types=1);

namespace App\Support;

/** Uang/biaya: numeric(14,4) (Architecture §3.1). Satu mata uang (IDR), tanpa konversi (ISS-006). */
final class Money extends Decimal
{
    protected const SCALE = 4;

    protected const PRECISION = 14;
}
