<?php

declare(strict_types=1);

namespace App\Support\Contracts;

/** Job yang berjalan dalam satu lokasi eksplisit (dipakai middleware RunsInLocation). */
interface LocationAware
{
    public function locationId(): int;
}
