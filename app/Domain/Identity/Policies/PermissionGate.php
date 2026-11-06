<?php

declare(strict_types=1);

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use LogicException;

/**
 * Pengecekan izin per aksi di Gate (R-03), pengganti Gate::before bawaan spatie:
 *  - hanya izin di katalog Permission yang dinilai; ability lain (`view`, `update`, ...) diteruskan ke Policy;
 *  - izin yang dipanggil dengan argumen (`can('location.manage', $lokasi)`) ditolak keras: izin tidak memeriksa
 *    lokasi, jadi akses ke data tertentu wajib lewat ability Policy;
 *  - izin yang ada di database tetapi tidak di katalog tidak pernah lolos (gagal tertutup);
 *  - user nonaktif tidak punya izin apa pun.
 */
final class PermissionGate
{
    /** @param array<int, mixed> $arguments */
    public function __invoke(Authenticatable $user, string $ability, array $arguments): ?bool
    {
        $permission = Permission::tryFrom($ability);

        if ($permission === null) {
            return null;
        }

        if ($arguments !== []) {
            throw new LogicException("Izin '{$ability}' tidak menerima argumen; untuk data tertentu pakai ability Policy.");
        }

        return $user instanceof User && self::allows($user, $permission);
    }

    public static function allows(User $user, Permission $permission): bool
    {
        return $user->is_active === true && $user->checkPermissionTo($permission->value);
    }
}
