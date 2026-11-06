<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Location;
use App\Support\Audit\AuditTrail;
use App\Support\Authorization\Actor;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Gate;

/**
 * Menetapkan cutover timestamp lokasi (MG-03): transaksi POS sebelum waktu ini diabaikan, jadi ini aksi sensitif (F5).
 * Satu-satunya jalan mengubah `cutover_at`. Pelaku diambil dari sesi login dan wajib `location.manage` (ISS-017).
 */
final class SetLocationCutover
{
    public function __construct(private readonly AuditTrail $audit) {}

    public function handle(Location $location, CarbonInterface $cutoverAt): Location
    {
        $actor = Actor::current();
        Gate::forUser($actor)->authorize('setCutover', $location);

        $before = $location->cutover_at?->toIso8601String();
        $location->forceFill(['cutover_at' => $cutoverAt->clone()->utc()])->save();

        $this->audit->record('location.cutover_changed', [
            'location_id' => $location->id,
            'before' => $before,
            'after' => $location->cutover_at?->toIso8601String(),
        ], $actor->id);

        return $location;
    }
}
