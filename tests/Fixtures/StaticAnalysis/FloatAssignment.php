<?php

declare(strict_types=1);

namespace Tests\Fixtures\StaticAnalysis;

use Tests\Fixtures\Models\SampleLine;

/** Sengaja salah: harus ditolak Larastan (T1.01). Dikecualikan dari analisis utama. */
final class FloatAssignment
{
    public function run(SampleLine $line): void
    {
        $line->quantity = 1.5;
        $line->unit_cost = 0.1 + 0.2;
    }
}
