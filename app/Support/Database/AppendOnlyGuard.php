<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Lapis terakhir di database (L-01): trigger yang menolak UPDATE dan DELETE pada tabel, termasuk dari SQL mentah,
 * query builder, atau proses lain. Pasang di migrasi tabel yang harus append-only (audit, dan nanti stock_movements):
 *
 *     AppendOnlyGuard::attach('activity_log');   // di up(), setelah Schema::create
 *     AppendOnlyGuard::detach('activity_log');   // di down(), sebelum drop tabel
 */
final class AppendOnlyGuard
{
    public static function attach(string $table): void
    {
        $table = self::identifier($table);
        DB::unprepared("CREATE TRIGGER {$table}_append_only BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation()");
    }

    public static function detach(string $table): void
    {
        $table = self::identifier($table);
        DB::unprepared("DROP TRIGGER IF EXISTS {$table}_append_only ON {$table}");
    }

    private static function identifier(string $name): string
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException("Nama tabel tidak valid: {$name}");
        }

        return $name;
    }
}
