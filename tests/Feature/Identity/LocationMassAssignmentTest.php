<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\SetLocationCutover;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Location;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Tests\Support\AuthHelpers;
use Tests\Support\Rbac;

use function Pest\Laravel\actingAs;

/** F5: cutover_at dan is_active tidak boleh berubah lewat mass assignment. */
it('F5: cutover_at dan is_active tidak bisa diisi lewat create/fill/update', function (string $field, mixed $value) {
    expect(fn () => Location::query()->create(['code' => 'X1', 'name' => 'X', $field => $value]))
        ->toThrow(MassAssignmentException::class);

    $location = Location::factory()->create();
    expect(fn () => $location->update([$field => $value]))->toThrow(MassAssignmentException::class);
    expect(fn () => $location->fill([$field => $value]))->toThrow(MassAssignmentException::class);
})->with([
    ['cutover_at', '2030-01-01 00:00:00'],
    ['is_active', false],
    ['id', 999],
    ['deleted_at', '2026-01-01 00:00:00'],
]);

it('F5: atribut yang sah tetap bisa diisi', function () {
    $location = Location::query()->create(['code' => 'OK1', 'name' => 'Toko OK', 'timezone' => 'Asia/Jayapura', 'opening_time' => '07:00', 'closing_time' => '21:00']);
    expect($location->timezone)->toBe('Asia/Jayapura');
});

it('F5/ISS-017: cutover_at hanya lewat Action; pelaku dari sesi login, nilai sebelum/sesudah diaudit', function () {
    Rbac::seed();
    $audit = AuthHelpers::fakeAudit();
    $admin = Rbac::user(Role::SuperAdmin);
    $location = Location::factory()->create();

    actingAs($admin);
    app(SetLocationCutover::class)->handle($location, CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Jakarta'));

    expect($location->fresh()->cutover_at->utc()->format('Y-m-d H:i'))->toBe('2026-10-31 17:00');
    $record = $audit->last('location.cutover_changed');
    expect($record)->not->toBeNull()
        ->and($record['actor'])->toBe($admin->id)
        ->and($record['context']['location_id'])->toBe($location->id)
        ->and($record['context']['before'])->toBeNull()
        ->and($record['context']['after'])->toBe('2026-10-31T17:00:00+00:00');
});

it('ISS-017: Action cutover menolak tanpa login dan tanpa location.manage', function () {
    Rbac::seed();
    $audit = AuthHelpers::fakeAudit();
    $location = Location::factory()->create();
    $manager = Rbac::user(Role::StoreManager);
    $manager->locations()->attach($location);

    expect(fn () => app(SetLocationCutover::class)->handle($location, CarbonImmutable::now()))
        ->toThrow(AuthorizationException::class);

    actingAs($manager);
    expect(fn () => app(SetLocationCutover::class)->handle($location, CarbonImmutable::now()))
        ->toThrow(AuthorizationException::class);

    expect($location->fresh()->cutover_at)->toBeNull()
        ->and($audit->count('location.cutover_changed'))->toBe(0);
});

it('F5: aktivasi lewat method eksplisit, bukan mass assignment', function () {
    $location = Location::factory()->create();
    $location->deactivate();
    expect($location->fresh()->is_active)->toBeFalse();
    $location->activate();
    expect($location->fresh()->is_active)->toBeTrue();
});
