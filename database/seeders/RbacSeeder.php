<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Izin dan role awal sesuai matriks PRD §4.3. Aman dijalankan ulang, termasuk di produksi: izin yang belum ada
 * dibuat; role yang belum ada dibuat dengan izin default; role yang sudah ada tidak disentuh supaya penyesuaian
 * Super Admin (R-03) tidak tertimpa. Izin baru untuk role lama ditambahkan lewat migration/seeder task-nya.
 */
final class RbacSeeder extends Seeder
{
    public const GUARD = 'web';

    public function run(PermissionRegistrar $registrar): void
    {
        $registrar->forgetCachedPermissions();

        DB::transaction(function () {
            $existing = PermissionModel::query()->where('guard_name', self::GUARD)->pluck('name')->all();

            foreach (Permission::cases() as $permission) {
                if (! in_array($permission->value, $existing, true)) {
                    PermissionModel::query()->create(['name' => $permission->value, 'guard_name' => self::GUARD]);
                }
            }

            $models = PermissionModel::query()->where('guard_name', self::GUARD)->get()->keyBy('name');

            foreach (Role::cases() as $role) {
                if (RoleModel::query()->where('name', $role->value)->where('guard_name', self::GUARD)->exists()) {
                    continue;
                }

                RoleModel::query()->create(['name' => $role->value, 'guard_name' => self::GUARD])
                    ->givePermissionTo(array_map(fn (Permission $p) => $models[$p->value], $role->defaultPermissions()));
            }
        });

        $registrar->forgetCachedPermissions();
    }
}
