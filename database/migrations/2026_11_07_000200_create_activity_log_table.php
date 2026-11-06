<?php

use App\Support\Database\AppendOnlyGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tabel audit (US-1.25, L-01) sesuai ERD: docs/erd/tables.md#activity_log. Append-only (AppendOnlyGuard). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 80)->nullable();
            $table->text('description');
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event', 60)->nullable();
            $table->string('causer_type', 120)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->jsonb('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestampTz('created_at');

            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index('created_at');
        });

        AppendOnlyGuard::attach('activity_log');
    }

    public function down(): void
    {
        AppendOnlyGuard::detach('activity_log');
        Schema::dropIfExists('activity_log');
    }
};
