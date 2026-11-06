<?php

declare(strict_types=1);

namespace App\Support\Contracts;

/** User yang dibatasi oleh lokasi (ADR-003). */
interface HasLocationAccess
{
    /** @return list<int> ID lokasi yang boleh diakses. */
    public function locationIds(): array;
}
