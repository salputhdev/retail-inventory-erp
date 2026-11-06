<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Audit\AuditTrail;
use App\Support\Audit\Redactor;

/** Menangkap catatan audit di test. Konteks diproses Redactor seperti implementasi nyata. */
final class FakeAuditTrail implements AuditTrail
{
    /** @var list<array{action: string, context: array<array-key, mixed>, actor: int|null}> */
    public array $records = [];

    public function record(string $action, array $context = [], ?int $actorId = null): void
    {
        $this->records[] = ['action' => $action, 'context' => Redactor::clean($context), 'actor' => $actorId];
    }

    /** @return list<string> */
    public function actions(): array
    {
        return array_column($this->records, 'action');
    }

    public function count(string $action): int
    {
        return count(array_filter($this->records, fn ($r) => $r['action'] === $action));
    }

    /** @return array{action: string, context: array<array-key, mixed>, actor: int|null}|null */
    public function last(string $action): ?array
    {
        $found = array_values(array_filter($this->records, fn ($r) => $r['action'] === $action));

        return $found === [] ? null : end($found);
    }
}
