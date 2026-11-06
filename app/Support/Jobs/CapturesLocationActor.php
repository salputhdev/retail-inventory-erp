<?php

declare(strict_types=1);

namespace App\Support\Jobs;

use App\Support\LocationContext;
use App\Support\LocationWriteGuard;
use Illuminate\Support\Facades\Auth;

/**
 * Untuk job LocationAware. Panggil bindLocation() di konstruktor:
 *  - saat DISPATCH: lokasi harus boleh dipakai pemicunya (user A tidak bisa men-dispatch job untuk lokasi B);
 *  - menyimpan ID pemicu agar RunsInLocation memeriksa ulang akses saat job benar-benar berjalan (worker tidak punya Auth).
 */
trait CapturesLocationActor
{
    private int $boundLocationId;

    private ?int $actorUserId = null;

    protected function bindLocation(int|string $locationId): void
    {
        $id = LocationWriteGuard::normalize($locationId);
        app(LocationContext::class)->assertAllowed($id);

        $this->boundLocationId = $id;
        $actor = Auth::id();
        $this->actorUserId = $actor === null ? null : (int) $actor;
    }

    public function locationId(): int
    {
        return $this->boundLocationId;
    }

    public function triggeredByUserId(): ?int
    {
        return $this->actorUserId;
    }
}
