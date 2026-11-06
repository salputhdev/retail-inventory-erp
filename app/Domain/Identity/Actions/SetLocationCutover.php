<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Location;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Menetapkan cutover timestamp lokasi (MG-03): transaksi POS sebelum waktu ini diabaikan, jadi ini aksi sensitif (F5).
 * Satu-satunya jalan mengubah `cutover_at`. Pencatatan ke audit log (activitylog) ditambahkan di T1.07; sementara
 * perubahan dicatat ke log aplikasi dengan nilai sebelum/sesudah.
 */
final class SetLocationCutover
{
    public function handle(Location $location, CarbonInterface $cutoverAt, ?int $actorUserId): Location
    {
        if ($actorUserId === null) {
            throw new InvalidArgumentException('Pelaku perubahan cutover wajib diketahui.');
        }

        $before = $location->cutover_at?->toIso8601String();
        $location->forceFill(['cutover_at' => $cutoverAt->clone()->utc()])->save();

        Log::info('location.cutover_changed', [
            'location_id' => $location->id,
            'actor_user_id' => $actorUserId,
            'before' => $before,
            'after' => $location->cutover_at?->toIso8601String(),
        ]);

        return $location;
    }
}
