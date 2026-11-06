<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('timezone', 60)->default('Asia/Jakarta'); // IANA (I-01)
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->timestampTz('cutover_at')->nullable(); // MG-03: transaksi POS sebelumnya diabaikan
            $table->boolean('is_active')->default(true);
            $table->softDeletesTz();
            $table->timestampsTz();
        });

        Schema::create('location_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->timestampsTz();
            $table->primary(['user_id', 'location_id']);
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_user');
        Schema::dropIfExists('locations');
    }
};
