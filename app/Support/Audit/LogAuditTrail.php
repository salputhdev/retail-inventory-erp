<?php

declare(strict_types=1);

namespace App\Support\Audit;

use Illuminate\Support\Facades\Log;

/** Implementasi sementara: baris JSON ke channel `audit` (retensi 365 hari, L-01). Diganti di T1.07. */
final class LogAuditTrail implements AuditTrail
{
    public function record(string $action, array $context = [], ?int $actorId = null): void
    {
        $request = request();

        Log::channel('audit')->info($action, Redactor::clean($context + [
            'actor_id' => $actorId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]));
    }
}
