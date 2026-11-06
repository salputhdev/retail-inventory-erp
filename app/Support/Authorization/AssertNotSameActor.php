<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Support\Exceptions\SegregationOfDutiesViolation;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * Pemisahan tugas (R-02): penyetuju bukan pembuat dokumen (adjustment, saldo awal) atau salah satu penghitung
 * (rekonsiliasi stock count). Gagal tertutup: pembuat kosong/tidak diketahui juga ditolak.
 * Dipakai ulang oleh RequiresApproval (T2.04) dan approval rekonsiliasi (T4.08).
 */
final class AssertNotSameActor
{
    /**
     * @throws SegregationOfDutiesViolation
     */
    public static function enforce(Authenticatable|int|string $approver, Authenticatable|int|string|null ...$makers): void
    {
        $approverId = self::id($approver);

        if ($makers === []) {
            throw SegregationOfDutiesViolation::unknownMaker();
        }

        foreach ($makers as $maker) {
            if ($maker === null) {
                throw SegregationOfDutiesViolation::unknownMaker();
            }

            if (self::id($maker) === $approverId) {
                throw SegregationOfDutiesViolation::sameActor();
            }
        }
    }

    public static function holds(Authenticatable|int|string $approver, Authenticatable|int|string|null ...$makers): bool
    {
        try {
            self::enforce($approver, ...$makers);
        } catch (SegregationOfDutiesViolation) {
            return false;
        }

        return true;
    }

    /** ID ketat: "05", "5 ", "5\n", "1_0" ditolak supaya perbandingan tidak bisa diakali lewat format. */
    private static function id(Authenticatable|int|string $actor): int
    {
        $id = $actor instanceof Authenticatable ? $actor->getAuthIdentifier() : $actor;

        if (is_int($id) && $id > 0) {
            return $id;
        }

        if (is_string($id) && preg_match('/^[1-9][0-9]{0,17}$/D', $id) === 1) {
            return (int) $id;
        }

        throw new InvalidArgumentException('ID pelaku tidak valid untuk pemeriksaan pemisahan tugas.');
    }
}
