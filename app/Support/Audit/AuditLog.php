<?php

declare(strict_types=1);

namespace App\Support\Audit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Spatie\Activitylog\Models\Activity;

/**
 * Baris audit (US-1.25, L-01). Tabel append-only: trigger database menolak UPDATE/DELETE (AppendOnlyGuard),
 * jadi `updated_at` sengaja tidak ada. Ditulis hanya lewat DatabaseAuditTrail, tidak pernah lewat form/route.
 *
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $event
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property int|null $location_id
 * @property string|null $ip_address
 * @property array<string, mixed>|null $properties
 * @property string|null $batch_uuid
 * @property Carbon $created_at
 */
#[Fillable(['log_name', 'description', 'subject_type', 'subject_id', 'event', 'causer_type', 'causer_id', 'location_id', 'ip_address', 'properties', 'batch_uuid', 'created_at'])]
class AuditLog extends Activity
{
    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
