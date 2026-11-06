<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Fungsi trigger bersama: baris tidak boleh diubah atau dihapus (L-01). Dipasang per tabel lewat AppendOnlyGuard::attach(). */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_row_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'tabel % tidak bisa diubah atau dihapus (append-only)', TG_TABLE_NAME USING ERRCODE = '23514';
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        // Trigger per tabel harus sudah dilepas (down() migrasi tabelnya) sebelum fungsi ini dihapus.
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_row_mutation()');
    }
};
