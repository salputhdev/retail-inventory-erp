<?php

declare(strict_types=1);

namespace App\Support\Audit;

use Illuminate\Database\Eloquent\Model;

/** Pencatat audit (L-01, US-1.25). Kontrak ini dipakai semua aksi sensitif; implementasi: `DatabaseAuditTrail`. */
interface AuditTrail
{
    /**
     * @param  string  $action  mis. `auth.login_failed`
     * @param  array<string, mixed>  $context  data tambahan (mis. nilai sebelum/sesudah); nilai sensitif disamarkan otomatis
     * @param  Model|null  $subject  entitas yang menjadi objek aksi (mis. Role yang izinnya diubah), bila ada
     */
    public function record(string $action, array $context = [], ?int $actorId = null, ?Model $subject = null): void;
}
