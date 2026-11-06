<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use RuntimeException;

/** Mencoba memakai/menulis lokasi di luar akses user atau konteks. */
final class LocationNotAllowed extends RuntimeException
{
    public static function for(int $locationId): self
    {
        return new self(sprintf('Lokasi %d di luar akses saat ini.', $locationId));
    }

    public static function ambiguous(): self
    {
        return new self('location_id harus diisi eksplisit: konteks memuat lebih dari satu lokasi.');
    }

    public static function immutable(): self
    {
        return new self('location_id tidak boleh diubah setelah dibuat; pindahkan stok lewat dokumen transfer.');
    }
}
