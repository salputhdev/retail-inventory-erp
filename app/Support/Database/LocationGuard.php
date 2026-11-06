<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Lapis terakhir di database (F4): trigger yang menolak perubahan location_id, termasuk dari SQL mentah,
 * query builder, atau proses lain. Pasang di setiap migrasi tabel transaksional:
 *
 *     LocationGuard::attach('stock_movements');   // di up(), setelah Schema::create
 *     LocationGuard::detach('stock_movements');   // di down(), sebelum drop tabel
 */
final class LocationGuard
{
    public static function attach(string $table): void
    {
        $table = self::identifier($table);
        DB::unprepared("CREATE TRIGGER {$table}_location_immutable BEFORE UPDATE OF location_id ON {$table} FOR EACH ROW EXECUTE FUNCTION prevent_location_id_change()");
    }

    public static function detach(string $table): void
    {
        $table = self::identifier($table);
        DB::unprepared("DROP TRIGGER IF EXISTS {$table}_location_immutable ON {$table}");
    }

    private static function identifier(string $name): string
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException("Nama tabel tidak valid: {$name}");
        }

        return $name;
    }
}
