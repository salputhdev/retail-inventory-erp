<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\Support\Rbac;

/** @return list<string> */
function seededPermissionNames(Role $role): array
{
    return RoleModel::query()->where('name', $role->value)->firstOrFail()->permissions()->pluck('name')->sort()->values()->all();
}

it('seeder aman dijalankan ulang dan tidak menimpa penyesuaian Super Admin', function () {
    Rbac::seed();
    RoleModel::query()->where('name', Role::StoreManager->value)->firstOrFail()->revokePermissionTo(Permission::CatalogManage->value);
    $customized = seededPermissionNames(Role::StoreManager);

    Rbac::seed();

    expect(seededPermissionNames(Role::StoreManager))->toBe($customized)
        ->and($customized)->not->toContain('catalog.manage')
        ->and(PermissionModel::query()->count())->toBe(count(Permission::cases()))
        ->and(RoleModel::query()->count())->toBe(count(Role::cases()));
});

it('seeder melengkapi izin katalog yang belum ada di database', function () {
    Rbac::seed();
    PermissionModel::findByName(Permission::ReportExport->value)->delete();

    Rbac::seed();

    expect(PermissionModel::query()->where('name', Permission::ReportExport->value)->exists())->toBeTrue();
});

it('role yang belum ada dibuat dengan izin default', function () {
    Rbac::seed();
    RoleModel::query()->where('name', Role::Auditor->value)->firstOrFail()->delete();

    Rbac::seed();

    $expected = array_map(fn (Permission $p) => $p->value, Role::Auditor->defaultPermissions());
    sort($expected);
    $actual = seededPermissionNames(Role::Auditor);
    sort($actual);
    expect($actual)->toBe($expected);
});
