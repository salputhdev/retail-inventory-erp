<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Support\Exceptions\MissingLocationContext;
use App\Support\LocationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Pencatat audit persisten (L-01, US-1.25, T1.07). Baris tidak bisa diubah/dihapus dari aplikasi (AppendOnlyGuard,
 * lihat migration `create_activity_log_table`). Menggantikan `LogAuditTrail` (channel log) tanpa mengubah pemanggil
 * selain menambah `$subject` opsional.
 */
final class DatabaseAuditTrail implements AuditTrail
{
    public function record(string $action, array $context = [], ?int $actorId = null, ?Model $subject = null): void
    {
        AuditLog::query()->create([
            'log_name' => 'audit',
            'description' => $action,
            'event' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'causer_type' => $actorId === null ? null : config('auth.providers.users.model'),
            'causer_id' => $actorId,
            'location_id' => $this->currentLocationId(),
            'ip_address' => Request::ip(),
            'properties' => Redactor::clean($context),
            'created_at' => now(),
        ]);
    }

    /** Lokasi sebagai konteks (kolom nullable): hanya diisi bila persis satu lokasi berlaku saat ini (ADR-003). */
    private function currentLocationId(): ?int
    {
        try {
            $ids = app(LocationContext::class)->ids();
        } catch (MissingLocationContext) {
            return null;
        }

        return count($ids) === 1 ? $ids[0] : null;
    }
}
