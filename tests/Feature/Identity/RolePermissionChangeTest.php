<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\SyncRolePermissions;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\Fixtures\Jobs\ProbePermissionJob;
use Tests\Support\AuthHelpers;
use Tests\Support\Rbac;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withSession;

/** @return list<Permission> */
function managerPermissionsWithout(Permission $removed): array
{
    return array_values(array_filter(Role::StoreManager->defaultPermissions(), fn (Permission $p) => $p !== $removed));
}

function roleModel(Role $role): RoleModel
{
    return RoleModel::query()->where('name', $role->value)->firstOrFail();
}

function storeManagerRole(): RoleModel
{
    return roleModel(Role::StoreManager);
}

/** @return list<string> */
function permissionNamesOf(RoleModel $role): array
{
    return $role->permissions()->pluck('name')->sort()->values()->all();
}

// ---- Kriteria 2: perubahan role/permission oleh Super Admin berlaku tanpa deploy
it('izin yang dicabut Super Admin langsung ditolak di request berikutnya', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);
    $manager = Rbac::user(Role::StoreManager);
    Route::middleware(['web', 'auth', 'can:catalog.manage'])->get('/_test/catalog-manage', fn () => ['ok' => true]);

    actingAs($manager);
    withSession(['auth.login_at' => now()->getTimestamp(), 'auth.last_activity' => now()->getTimestamp()]);
    getJson('/_test/catalog-manage')->assertOk();

    actingAs($admin);
    app(SyncRolePermissions::class)->handle(storeManagerRole(), managerPermissionsWithout(Permission::CatalogManage));

    actingAs($manager);
    getJson('/_test/catalog-manage')->assertForbidden();
});

it('izin yang ditambahkan Super Admin langsung berlaku', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);
    $staff = Rbac::user(Role::InventoryStaff);
    expect(Gate::forUser($staff)->allows(Permission::DashboardView))->toBeFalse();

    actingAs($admin);
    $role = roleModel(Role::InventoryStaff);
    app(SyncRolePermissions::class)->handle($role, [...Role::InventoryStaff->defaultPermissions(), Permission::DashboardView]);

    expect(Gate::forUser($staff->fresh())->allows(Permission::DashboardView))->toBeTrue();
});

it('worker queue melihat perubahan izin tanpa restart', function () {
    Rbac::seed();
    $manager = Rbac::user(Role::StoreManager);
    $roleId = storeManagerRole()->id;
    $permissionId = PermissionModel::findByName(Permission::CatalogManage->value)->id;
    expect(Gate::forUser($manager)->allows(Permission::CatalogManage))->toBeTrue(); // izin dimuat ke memori proses

    // Proses lain (request admin) mengubah izin: pivot dihapus dan cache bersama dibuang.
    DB::table('role_has_permissions')->where(['role_id' => $roleId, 'permission_id' => $permissionId])->delete();
    app(PermissionRegistrar::class)->getCacheRepository()->forget((string) config('permission.cache.key'));
    expect(Gate::forUser($manager->fresh())->allows(Permission::CatalogManage))->toBeTrue(); // memori proses ini masih basi

    ProbePermissionJob::$allowed = null;
    ProbePermissionJob::dispatch($manager->id, Permission::CatalogManage);

    expect(ProbePermissionJob::$allowed)->toBeFalse();
});

it('cache izin tidak tertinggal data lama bila request lain mengisinya sebelum commit', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);
    $manager = Rbac::user(Role::StoreManager);
    $key = (string) config('permission.cache.key');
    $cache = app(PermissionRegistrar::class)->getCacheRepository();
    expect(Gate::forUser($manager)->allows(Permission::CatalogManage))->toBeTrue();
    $stale = $cache->get($key);
    $refilled = false;

    // Request lain membaca izin lama (belum commit) dan menyimpannya lagi tepat setelah spatie membuang cache.
    Event::listen(KeyForgotten::class, function (KeyForgotten $event) use ($cache, $key, $stale, &$refilled) {
        if ($event->key === $key && ! $refilled) {
            $refilled = true;
            $cache->put($key, $stale);
        }
    });

    actingAs($admin);
    app(SyncRolePermissions::class)->handle(storeManagerRole(), managerPermissionsWithout(Permission::CatalogManage));

    expect($refilled)->toBeTrue()
        ->and(Gate::forUser($manager->fresh())->allows(Permission::CatalogManage))->toBeFalse();
});

it('perubahan izin tercatat di audit dengan nilai sebelum dan sesudah (US-1.24 AC1)', function () {
    Rbac::seed();
    $audit = AuthHelpers::fakeAudit();
    $admin = Rbac::user(Role::SuperAdmin);
    $before = permissionNamesOf(storeManagerRole());

    actingAs($admin);
    app(SyncRolePermissions::class)->handle(storeManagerRole(), managerPermissionsWithout(Permission::CatalogManage));

    $record = $audit->last('rbac.role_permissions_changed');
    expect($record)->not->toBeNull()
        ->and($record['actor'])->toBe($admin->id)
        ->and($record['context']['role'])->toBe('store-manager')
        ->and($record['context']['before'])->toBe($before)
        ->and($record['context']['before'])->toContain('catalog.manage')
        ->and($record['context']['after'])->not->toContain('catalog.manage')
        ->and($record['context']['after'])->toBe(permissionNamesOf(storeManagerRole()));
});

it('hanya pemegang role.manage yang bisa mengubah izin role', function (Role $role) {
    Rbac::seed();
    $audit = AuthHelpers::fakeAudit();
    $before = permissionNamesOf(storeManagerRole());

    actingAs(Rbac::user($role));

    expect(fn () => app(SyncRolePermissions::class)->handle(storeManagerRole(), Permission::cases()))
        ->toThrow(AuthorizationException::class);
    expect(permissionNamesOf(storeManagerRole()))->toBe($before)
        ->and($audit->count('rbac.role_permissions_changed'))->toBe(0);
})->with([Role::StoreManager, Role::InventoryStaff, Role::PosIntegrator, Role::Auditor]);

it('mengubah izin role tanpa login ditolak', function () {
    Rbac::seed();
    $before = permissionNamesOf(storeManagerRole());

    expect(fn () => app(SyncRolePermissions::class)->handle(storeManagerRole(), []))
        ->toThrow(AuthorizationException::class);
    expect(permissionNamesOf(storeManagerRole()))->toBe($before);
});

it('role Super Admin tidak bisa kehilangan izin kelola role', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);
    $role = roleModel(Role::SuperAdmin);
    $before = permissionNamesOf($role);

    actingAs($admin);

    expect(fn () => app(SyncRolePermissions::class)->handle($role, [Permission::UserManage]))
        ->toThrow(ValidationException::class);
    expect(permissionNamesOf($role))->toBe($before)
        ->and(Gate::forUser($admin)->allows(Permission::RoleManage))->toBeTrue();
});
