<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Identity\LocationFixtures;
use Tests\Fixtures\Models\SampleLocatedLine;
use Tests\Fixtures\Policies\SampleLocatedLinePolicy;
use Tests\Support\Rbac;

/** Dasar Policy model ber-lokasi: lokasi lain = 404 sebelum izin diperiksa, lokasi sendiri tanpa izin = 403. */
it('mengizinkan pemegang izin di lokasinya sendiri', function () {
    Rbac::seed();
    $d = LocationFixtures::boot();
    Gate::policy(SampleLocatedLine::class, SampleLocatedLinePolicy::class);
    $d['userA']->assignRole(Role::InventoryStaff->value);

    expect(Gate::forUser($d['userA'])->inspect('update', $d['lineA'])->allowed())->toBeTrue();
});

it('lokasi sendiri tanpa izin = 403', function () {
    Rbac::seed();
    $d = LocationFixtures::boot();
    Gate::policy(SampleLocatedLine::class, SampleLocatedLinePolicy::class);
    $d['userA']->assignRole(Role::Auditor->value);

    $result = Gate::forUser($d['userA'])->inspect('update', $d['lineA']);

    expect($result->denied())->toBeTrue()->and($result->status() ?? 403)->toBe(403);
});

it('lokasi lain = 404, baik punya izin maupun tidak (keberadaan data tidak bocor)', function (?Role $role) {
    Rbac::seed();
    $d = LocationFixtures::boot();
    Gate::policy(SampleLocatedLine::class, SampleLocatedLinePolicy::class);
    if ($role !== null) {
        $d['userA']->assignRole($role->value);
    }

    $result = Gate::forUser($d['userA'])->inspect('update', $d['lineB']);

    expect($result->denied())->toBeTrue()->and($result->status())->toBe(404);
})->with(['dengan izin' => [Role::InventoryStaff], 'tanpa izin' => [null]]);
