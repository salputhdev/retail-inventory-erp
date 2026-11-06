<?php

declare(strict_types=1);

namespace App\Support\Audit;

/**
 * Pencatat audit (L-01, US-1.25). Kontrak ini dipakai semua aksi sensitif; implementasi sementara menulis ke
 * channel log `audit`. T1.07 menggantinya dengan penyimpanan database yang tidak bisa diubah dari aplikasi,
 * tanpa mengubah pemanggil.
 */
interface AuditTrail
{
    /**
     * @param  string  $action  mis. `auth.login_failed`
     * @param  array<string, mixed>  $context  data tambahan; nilai sensitif disamarkan otomatis
     */
    public function record(string $action, array $context = [], ?int $actorId = null): void;
}
