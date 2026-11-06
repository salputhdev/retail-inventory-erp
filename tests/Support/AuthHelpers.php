<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Identity\Models\User;
use App\Support\Audit\AuditTrail;

/** Pembantu test autentikasi. */
final class AuthHelpers
{
    public const PASSWORD = 'Kata-sandi-aman-2026';

    /** Pasang pencatat audit palsu (menggantikan implementasi log). */
    public static function fakeAudit(): FakeAuditTrail
    {
        $fake = new FakeAuditTrail;
        app()->instance(AuditTrail::class, $fake);

        return $fake;
    }

    /** @param array<string, mixed> $attributes */
    public static function user(array $attributes = []): User
    {
        // fresh(): muat kolom bawaan database (mis. is_active) seperti user yang dimuat pada request nyata.
        return User::factory()->create(array_merge(['password' => self::PASSWORD], $attributes))->fresh();
    }
}
