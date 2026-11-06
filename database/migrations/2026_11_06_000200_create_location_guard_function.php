<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Fungsi trigger bersama: location_id tidak boleh berubah (F4). Dipasang per tabel lewat LocationGuard::attach(). */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_location_id_change() RETURNS trigger AS $$
            BEGIN
                IF NEW.location_id IS DISTINCT FROM OLD.location_id THEN
                    RAISE EXCEPTION 'location_id tidak boleh diubah (tabel %)', TG_TABLE_NAME USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        // Trigger per tabel harus sudah dilepas (down() migrasi tabelnya) sebelum fungsi ini dihapus.
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_location_id_change()');
    }
};
