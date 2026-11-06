<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\SetLocationCutover;
use App\Domain\Identity\Actions\SyncRolePermissions;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Location;
use App\Support\Audit\AuditLog;
use App\Support\Audit\AuditTrail;
use App\Support\Audit\DatabaseAuditTrail;
use App\Support\LocationContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\Fixtures\Audit\ThrowingAuditTrail;
use Tests\Support\AuthHelpers;
use Tests\Support\Rbac;

use function Pest\Laravel\actingAs;

// ---- Kriteria 1: pelaku, waktu, IP, aksi, subjek, dan nilai sebelum/sesudah
it('mencatat pelaku, waktu, IP, aksi, subjek, dan konteks sebelum/sesudah', function () {
    $actor = AuthHelpers::user();
    $location = Location::factory()->create();

    app(DatabaseAuditTrail::class)->record('contoh.aksi', ['before' => 'a', 'after' => 'b'], $actor->id, $location);

    $row = AuditLog::query()->latest('id')->firstOrFail();
    expect($row->description)->toBe('contoh.aksi')
        ->and($row->event)->toBe('contoh.aksi')
        ->and($row->causer_type)->toBe($actor::class)
        ->and($row->causer_id)->toBe($actor->id)
        ->and($row->subject_type)->toBe($location->getMorphClass())
        ->and($row->subject_id)->toBe($location->id)
        ->and($row->properties)->toEqual(['before' => 'a', 'after' => 'b'])
        ->and($row->ip_address)->not->toBeNull()
        ->and($row->created_at->diffInSeconds(now()))->toBeLessThan(5);
});

it('tanpa pelaku dan tanpa subjek: causer/subject kosong, bukan error', function () {
    app(DatabaseAuditTrail::class)->record('auth.login_failed', ['email' => 'x@example.test']);

    $row = AuditLog::query()->latest('id')->firstOrFail();
    expect($row->causer_type)->toBeNull()->and($row->causer_id)->toBeNull()
        ->and($row->subject_type)->toBeNull()->and($row->subject_id)->toBeNull();
});

// ---- Kriteria 2: tidak ada route/Action yang bisa mengubah atau menghapus entri
it('baris audit tidak bisa di-update atau di-delete lewat Eloquent', function () {
    app(DatabaseAuditTrail::class)->record('contoh.aksi');
    $row = AuditLog::query()->latest('id')->firstOrFail();

    // Savepoint per percobaan: Postgres mengabaikan sisa statement di transaksi yang sama setelah satu gagal.
    expect(fn () => DB::transaction(fn () => $row->update(['description' => 'diubah'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => $row->delete()))->toThrow(QueryException::class);
});

it('baris audit tidak bisa di-update atau di-delete lewat SQL mentah (trigger database)', function () {
    app(DatabaseAuditTrail::class)->record('contoh.aksi');
    $id = AuditLog::query()->latest('id')->value('id');

    // Savepoint per percobaan: Postgres mengabaikan sisa statement di transaksi yang sama setelah satu gagal.
    expect(fn () => DB::transaction(fn () => DB::table('activity_log')->where('id', $id)->update(['description' => 'diubah'])))
        ->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('activity_log')->where('id', $id)->delete()))
        ->toThrow(QueryException::class);

    expect(AuditLog::query()->find($id)->description)->toBe('contoh.aksi');
});

// ---- Kriteria 3: data sensitif tidak pernah masuk
it('data sensitif disamarkan sebelum tersimpan, bukan hanya saat dibaca', function () {
    app(DatabaseAuditTrail::class)->record('contoh.aksi', ['password' => 'rahasia-asli', 'ok' => 'boleh']);

    $raw = DB::table('activity_log')->latest('id')->value('properties');
    expect($raw)->not->toContain('rahasia-asli')->and($raw)->toContain('boleh');
});

// ---- Lokasi sebagai konteks (bukan kriteria eksplisit, tapi kolom ERD)
it('location_id diisi hanya bila tepat satu lokasi berlaku; ambigu (>1) atau tanpa lokasi = null', function () {
    [$locA, $locB] = Location::factory()->count(2)->create()->all();
    $audit = app(DatabaseAuditTrail::class);

    app(LocationContext::class)->run($locA->id, function () use ($audit) {
        $audit->record('satu.lokasi');
    });
    app(LocationContext::class)->run([$locA->id, $locB->id], function () use ($audit) {
        $audit->record('dua.lokasi');
    });
    app(LocationContext::class)->run([], function () use ($audit) {
        $audit->record('tanpa.lokasi');
    });
    $audit->record('tanpa.konteks');

    expect(AuditLog::query()->where('event', 'satu.lokasi')->value('location_id'))->toBe($locA->id)
        ->and(AuditLog::query()->where('event', 'dua.lokasi')->value('location_id'))->toBeNull()
        ->and(AuditLog::query()->where('event', 'tanpa.lokasi')->value('location_id'))->toBeNull()
        ->and(AuditLog::query()->where('event', 'tanpa.konteks')->value('location_id'))->toBeNull();
});

// ---- ISS-027: perubahan + audit dalam satu transaksi
it('ISS-027: bila penulisan audit gagal, perubahan izin role ikut batal (tidak ada yang tersimpan tanpa jejak)', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);
    $role = RoleModel::query()->where('name', Role::StoreManager->value)->firstOrFail();
    $before = $role->permissions()->pluck('name')->sort()->values()->all();

    app()->instance(AuditTrail::class, new ThrowingAuditTrail);
    actingAs($admin);

    expect(fn () => app(SyncRolePermissions::class)->handle($role, []))->toThrow(RuntimeException::class);
    expect($role->permissions()->pluck('name')->sort()->values()->all())->toBe($before);
});

it('ISS-027: bila penulisan audit gagal, cutover_at lokasi ikut batal', function () {
    Rbac::seed();
    $admin = Rbac::user(Role::SuperAdmin);
    $location = Location::factory()->create();

    app()->instance(AuditTrail::class, new ThrowingAuditTrail);
    actingAs($admin);

    expect(fn () => app(SetLocationCutover::class)->handle($location, CarbonImmutable::now()))->toThrow(RuntimeException::class);
    expect($location->fresh()->cutover_at)->toBeNull();
});
