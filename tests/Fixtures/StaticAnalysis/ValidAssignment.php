<?php

declare(strict_types=1);

namespace Tests\Fixtures\StaticAnalysis;

use App\Support\Money;
use App\Support\Quantity;
use Tests\Fixtures\Models\SampleLine;

/** Penugasan yang sah harus lolos Larastan. */
final class ValidAssignment
{
    public function run(SampleLine $line): void
    {
        $line->quantity = Quantity::of('1.5');
        $line->quantity = '2.000';
        $line->quantity = 3;
        $line->unit_cost = Money::of('5000');
    }
}
