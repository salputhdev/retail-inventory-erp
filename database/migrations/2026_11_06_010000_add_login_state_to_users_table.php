<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password'); // nonaktif = tidak bisa login, sesi dicabut (US-1.24)
            $table->timestampTz('last_login_at')->nullable()->after('is_active');
            $table->timestampTz('password_changed_at')->nullable()->after('last_login_at'); // sesi yang dibuat sebelum ini dicabut
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at', 'password_changed_at']);
        });
    }
};
