<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Location;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\Support\AuthHelpers;
use Tests\Support\PrdRbacMatrix;
use Tests\Support\Rbac;

// ---- Kriteria 1: setiap sel matriks PRD §4.3 (izin dan larangan) untuk kelima role
it('menegakkan sel matriks PRD §4.3', function (string $role, string $permission, bool $allowed) {
    Rbac::seed();
    $user = Rbac::user(Role::from($role));

    expect(Gate::forUser($user)->allows($permission))->toBe($allowed)
        ->and($user->can($permission))->toBe($allowed);
})->with(PrdRbacMatrix::cells());

it('katalog izin dan role sama persis dengan matriks PRD (tidak ada izin yang tidak teruji)', function () {
    $fromPrd = array_keys(PrdRbacMatrix::expected());
    $fromCode = array_map(fn (Permission $p) => $p->value, Permission::cases());
    sort($fromPrd);
    sort($fromCode);

    expect($fromCode)->toBe($fromPrd)
        ->and(array_map(fn (Role $r) => $r->value, Role::cases()))->toBe(PrdRbacMatrix::ROLES)
        ->and(count(PrdRbacMatrix::cells()))->toBe(count(Permission::cases()) * 5);
});

it('seeder membuat tepat izin dan role katalog', function () {
    Rbac::seed();

    $permissions = PermissionModel::query()->pluck('name')->all();
    $expected = array_map(fn (Permission $p) => $p->value, Permission::cases());
    sort($permissions);
    sort($expected);

    expect($permissions)->toBe($expected)
        ->and(RoleModel::query()->orderBy('id')->pluck('name')->all())->toBe(PrdRbacMatrix::ROLES);
});

// ---- Gagal tertutup
it('izin di database yang tidak ada di katalog tidak pernah lolos Gate', function () {
    Rbac::seed();
    PermissionModel::create(['name' => 'catalog.delete_all', 'guard_name' => 'web']);
    RoleModel::query()->where('name', Role::StoreManager->value)->firstOrFail()->givePermissionTo('catalog.delete_all');
    $manager = Rbac::user(Role::StoreManager);

    expect($manager->checkPermissionTo('catalog.delete_all'))->toBeTrue() // spatie sendiri akan mengizinkan
        ->and(Gate::forUser($manager)->allows('catalog.delete_all'))->toBeFalse();
});

it('izin yang dipanggil dengan model ditolak keras agar Policy (lokasi) tidak terlewati', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);
    $location = Location::factory()->create();

    expect(fn () => Gate::forUser($admin)->allows('location.manage', $location))
        ->toThrow(LogicException::class, 'tidak menerima argumen');
});

it('user nonaktif tidak punya izin apa pun', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin, ['is_active' => false]);

    expect(Gate::forUser($admin)->allows(Permission::RoleManage))->toBeFalse()
        ->and(Gate::forUser($admin)->allows(Permission::CatalogView))->toBeFalse();
});

it('guest tidak punya izin apa pun', function () {
    Rbac::seed();

    expect(Gate::forUser(null)->allows(Permission::CatalogView))->toBeFalse()
        ->and(Gate::allows(Permission::DashboardView))->toBeFalse();
});

it('role baru dibuat tanpa izin (R-04 least privilege)', function () {
    Rbac::seed();
    RoleModel::create(['name' => 'regional-manager', 'guard_name' => 'web']);
    $user = AuthHelpers::user();
    $user->assignRole('regional-manager');

    foreach (Permission::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission))->toBeFalse();
    }
});

it('Super Admin tidak mendapat bypass: izin transaksi stok tetap ditolak', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);

    expect(Gate::forUser($admin)->any([
        Permission::GoodsReceiptPost, Permission::TransferPost, Permission::ReturnPost,
        Permission::AdjustmentCreate, Permission::AdjustmentApprove, Permission::ReversalCreate,
        Permission::StockCountCount, Permission::ReconciliationApprove,
    ]))->toBeFalse();
});
