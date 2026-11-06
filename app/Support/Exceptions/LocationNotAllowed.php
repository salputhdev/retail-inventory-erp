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

    public static function invalid(mixed $value): self
    {
        return new self(sprintf('location_id tidak valid (%s): harus bilangan bulat positif murni.', get_debug_type($value)));
    }

    public static function unknownPrincipal(): self
    {
        return new self('Principal yang login tidak mengimplementasi HasLocationAccess; akses lokasi ditolak.');
    }

    public static function systemOnly(): self
    {
        return new self('Konteks sistem hanya boleh dibuka dari proses console (seeder, command, job queue).');
    }

    public static function immutable(): self
    {
        return new self('location_id tidak boleh diubah setelah dibuat; pindahkan stok lewat dokumen transfer.');
    }
}
