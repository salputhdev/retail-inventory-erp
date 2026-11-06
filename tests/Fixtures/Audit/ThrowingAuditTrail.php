<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Support\Audit\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/** Simula kegagalan penulisan audit (ISS-027): perubahan + audit harus satu transaksi, jadi perubahan ikut batal. */
final class ThrowingAuditTrail implements AuditTrail
{
    public function record(string $action, array $context = [], ?int $actorId = null, ?Model $subject = null): void
    {
        throw new RuntimeException('audit.write_failed (simulasi)');
    }
}
