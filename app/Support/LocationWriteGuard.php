<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Exceptions\LocationNotAllowed;

/**
 * Satu-satunya tempat yang memutuskan boleh/tidaknya sebuah location_id DITULIS (insert/upsert/update).
 * Dipakai oleh model (performInsert/performUpdate) dan query builder, jadi saveQuietly(), withoutEvents(),
 * insert(), upsert(), dan update() massal tidak bisa menghindarinya. Gagal tertutup (R-01, S-06).
 */
final class LocationWriteGuard
{
    /**
     * Ubah nilai mentah menjadi int dengan validasi KETAT. Nilai yang diperiksa harus sama persis dengan yang
     * dikirim ke database: PHP (int)"1_0" = 1, tetapi PostgreSQL 16+ membaca '1_0'::bigint = 10 (F1).
     */
    public static function normalize(mixed $value): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[1-9][0-9]{0,17}$/', $value) === 1) {
            return (int) $value;
        }

        throw LocationNotAllowed::invalid($value);
    }

    /** @throws LocationNotAllowed|Exceptions\MissingLocationContext */
    public static function assertWritable(mixed $rawLocationId): int
    {
        $id = self::normalize($rawLocationId);
        $context = app(LocationContext::class);

        if ($context->isSystem()) {
            return $id;
        }
        if (! in_array($id, $context->ids(), true)) { // ids() gagal tertutup bila tidak ada konteks (F3)
            throw LocationNotAllowed::for($id);
        }

        return $id;
    }

    /**
     * Untuk insert/upsert massal: setiap baris wajib membawa location_id eksplisit yang boleh ditulis.
     *
     * @param  array<int|string, mixed>  $values  satu baris (assoc) atau daftar baris
     */
    public static function assertRows(array $values): void
    {
        $rows = ($values !== [] && array_is_list($values) && is_array($values[0])) ? $values : [$values];

        foreach ($rows as $row) {
            if (! is_array($row) || ! array_key_exists('location_id', $row)) {
                throw LocationNotAllowed::invalid(null);
            }
            self::assertWritable($row['location_id']);
        }
    }

    /** @param array<int|string, mixed> $columns kolom yang akan di-update (daftar nama atau assoc) */
    public static function assertNotChangingLocation(array $columns): void
    {
        if (array_key_exists('location_id', $columns) || in_array('location_id', $columns, true)) {
            throw LocationNotAllowed::immutable();
        }
    }

    /**
     * Upsert harus memakai location_id sebagai bagian dari kunci konflik, supaya baris lokasi lain tidak ikut
     * tertimpa. Indeks unik tabel transaksional wajib menyertakan location_id (ADR-003).
     *
     * @param  array<int|string, string>|string  $uniqueBy
     */
    public static function assertConflictKeyIncludesLocation(array|string $uniqueBy): void
    {
        $columns = is_array($uniqueBy) ? $uniqueBy : [$uniqueBy];

        if (! in_array('location_id', $columns, true)) {
            throw new LocationNotAllowed('upsert() wajib menyertakan location_id pada kunci konflik (uniqueBy); kunci tanpa location_id dapat menimpa baris lokasi lain.');
        }
    }
}
