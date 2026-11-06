<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Location;
use App\Domain\Identity\Policies\LocationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\Support\Rbac;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withSession;

// ---- ISS-014: Location dibatasi Policy (lokasi bukan model ber-scope)
it('Policy Location terdaftar', function () {
    expect(Gate::getPolicyFor(Location::class))->toBeInstanceOf(LocationPolicy::class);
});

it('user hanya melihat lokasi yang di-assign; lokasi lain 404', function (Role $role) {
    Rbac::seed();
    [$own, $other] = Location::factory()->count(2)->create()->all();
    $user = Rbac::user($role);
    $user->locations()->attach($own);

    $denied = Gate::forUser($user)->inspect('view', $other);

    expect(Gate::forUser($user)->allows('view', $own))->toBeTrue()
        ->and($denied->denied())->toBeTrue()
        ->and($denied->status())->toBe(404);
})->with([Role::StoreManager, Role::InventoryStaff, Role::Auditor, Role::PosIntegrator]);

it('akses langsung ke lokasi lain lewat route model binding = 404', function () {
    Rbac::seed();
    $own = Location::factory()->create(['code' => 'OWN1']);
    $other = Location::factory()->create(['code' => 'OTHER1']);
    $manager = Rbac::user(Role::StoreManager);
    $manager->locations()->attach($own);
    Route::middleware(['web', 'auth'])->get('/_test/locations/{location}', fn (Location $location) => ['code' => $location->code])
        ->middleware('can:view,location');

    actingAs($manager);
    withSession(['auth.login_at' => now()->getTimestamp(), 'auth.last_activity' => now()->getTimestamp()]);

    getJson('/_test/locations/'.$own->id)->assertOk()->assertJson(['code' => 'OWN1']);
    getJson('/_test/locations/'.$other->id)->assertNotFound()->assertDontSee('OTHER1');
});

it('Super Admin (location.manage) melihat dan mengelola semua lokasi tanpa di-assign', function () {
    Rbac::seed();
    $location = Location::factory()->create();
    $admin = Rbac::user(Role::SuperAdmin);
    $gate = Gate::forUser($admin);

    expect($gate->allows('viewAny', Location::class))->toBeTrue()
        ->and($gate->allows('create', Location::class))->toBeTrue()
        ->and($gate->allows('view', $location))->toBeTrue()
        ->and($gate->allows('update', $location))->toBeTrue()
        ->and($gate->allows('delete', $location))->toBeTrue()
        ->and($gate->allows('setCutover', $location))->toBeTrue();
});

it('role lain tidak bisa mengelola lokasi: lokasinya sendiri 403, lokasi lain 404', function (Role $role, string $ability) {
    Rbac::seed();
    [$own, $other] = Location::factory()->count(2)->create()->all();
    $user = Rbac::user($role);
    $user->locations()->attach($own);
    $gate = Gate::forUser($user);

    $ownResult = $gate->inspect($ability, $own);
    $otherResult = $gate->inspect($ability, $other);

    // Response::deny() tanpa kode dirender Laravel sebagai 403.
    expect($ownResult->denied())->toBeTrue()
        ->and($ownResult->status() ?? 403)->toBe(403)
        ->and($otherResult->denied())->toBeTrue()
        ->and($otherResult->status())->toBe(404)
        ->and($gate->allows('viewAny', Location::class))->toBeFalse()
        ->and($gate->allows('create', Location::class))->toBeFalse();
})->with([Role::StoreManager, Role::InventoryStaff, Role::Auditor])->with(['update', 'delete', 'setCutover']);
