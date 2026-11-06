<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use RuntimeException;

/** Query ber-scope lokasi dijalankan tanpa user dan tanpa konteks eksplisit. Gagal tertutup, bukan mengembalikan semua data. */
final class MissingLocationContext extends RuntimeException
{
    public static function make(): self
    {
        return new self('Tidak ada konteks lokasi. Login sebagai user ber-lokasi, bungkus dengan LocationContext::run(), atau pakai withoutLocationScope() secara eksplisit.');
    }
}
