<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

/** Penyetuju sama dengan pembuat/penghitung dokumen, atau pembuatnya tidak diketahui (R-02). Dirender 403. */
final class SegregationOfDutiesViolation extends AuthorizationException
{
    public static function sameActor(): self
    {
        return new self('Penyetuju tidak boleh sama dengan pembuat atau penghitung dokumen.');
    }

    public static function unknownMaker(): self
    {
        return new self('Pembuat dokumen tidak diketahui, jadi pemisahan tugas tidak bisa dibuktikan.');
    }
}
