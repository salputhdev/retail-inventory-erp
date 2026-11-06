<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Support\Audit\AuditTrail;
use App\Support\Authorization\Actor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Mengganti seluruh izin sebuah role (R-03, US-1.24 AC1): berlaku tanpa deploy. Hanya pemegang `role.manage`.
 * Role super-admin tidak bisa kehilangan `role.manage`, supaya tidak ada yang tersisa untuk memulihkan akses.
 */
final class SyncRolePermissions
{
    public function __construct(
        private readonly AuditTrail $audit,
        private readonly PermissionRegistrar $registrar,
    ) {}

    /** @param list<Permission> $permissions */
    public function handle(RoleModel $role, array $permissions): RoleModel
    {
        $actor = Actor::current();
        Gate::forUser($actor)->authorize(Permission::RoleManage);

        $after = array_values(array_unique(array_map(fn (Permission $p) => $p->value, $permissions)));
        sort($after);

        if ($role->name === Role::SuperAdmin->value && ! in_array(Permission::RoleManage->value, $after, true)) {
            throw ValidationException::withMessages([
                'permissions' => 'Role Super Admin harus tetap memiliki izin kelola role.',
            ]);
        }

        $before = $role->permissions()->pluck('name')->sort()->values()->all();

        DB::transaction(fn () => $role->syncPermissions($after));

        // Spatie membuang cache di dalam transaksi; request lain bisa mengisinya lagi dengan data lama sebelum commit.
        $this->registrar->forgetCachedPermissions();

        $this->audit->record('rbac.role_permissions_changed', [
            'role' => $role->name,
            'before' => $before,
            'after' => $after,
        ], $actor->id);

        return $role;
    }
}
