<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RbacSeeder;

/** Pembantu test RBAC: seed matriks default dan buat user ber-role. */
final class Rbac
{
    public static function seed(): void
    {
        app(RbacSeeder::class)->setContainer(app())->__invoke();
    }

    /** @param array<string, mixed> $attributes */
    public static function user(Role $role, array $attributes = []): User
    {
        $user = AuthHelpers::user($attributes);
        $user->assignRole($role->value);

        return $user;
    }
}
