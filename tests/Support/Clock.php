<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/** Memindahkan waktu aplikasi di test (dikembalikan otomatis di tearDown TestCase Laravel). */
final class Clock
{
    public static function at(DateTimeInterface $instant): void
    {
        Carbon::setTestNow($instant);
    }
}
