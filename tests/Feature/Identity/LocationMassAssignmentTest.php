<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\SetLocationCutover;
use App\Domain\Identity\Models\Location;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Log;

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

it('F5: cutover_at hanya lewat Action khusus yang mencatat pelaku dan nilai sebelum/sesudah', function () {
    $log = Log::spy();
    $location = Location::factory()->create();

    app(SetLocationCutover::class)->handle($location, CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Jakarta'), actorUserId: 7);

    expect($location->fresh()->cutover_at->utc()->format('Y-m-d H:i'))->toBe('2026-10-31 17:00');
    $log->shouldHaveReceived('info')->withArgs(fn ($msg, $ctx) => $msg === 'location.cutover_changed'
        && $ctx['actor_user_id'] === 7 && $ctx['before'] === null && $ctx['location_id'] === $location->id)->once();
});

it('F5: Action cutover menolak pelaku tak dikenal', function () {
    $location = Location::factory()->create();
    expect(fn () => app(SetLocationCutover::class)->handle($location, CarbonImmutable::now(), actorUserId: null))
        ->toThrow(InvalidArgumentException::class);
});

it('F5: aktivasi lewat method eksplisit, bukan mass assignment', function () {
    $location = Location::factory()->create();
    $location->deactivate();
    expect($location->fresh()->is_active)->toBeFalse();
    $location->activate();
    expect($location->fresh()->is_active)->toBeTrue();
});
