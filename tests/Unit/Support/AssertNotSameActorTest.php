<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Support\Authorization\AssertNotSameActor;
use App\Support\Exceptions\SegregationOfDutiesViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;

function actor(int $id): User
{
    return (new User)->forceFill(['id' => $id]);
}

// ---- Kriteria 3: helper segregasi menolak approval oleh pembuat dokumen (R-02)
it('menolak approval oleh pembuat dokumen', function () {
    expect(fn () => AssertNotSameActor::enforce(5, 5))
        ->toThrow(SegregationOfDutiesViolation::class, 'Penyetuju tidak boleh sama');
});

it('mengizinkan penyetuju yang bukan pembuat', function () {
    AssertNotSameActor::enforce(actor(6), actor(5));

    expect(AssertNotSameActor::holds(6, 5))->toBeTrue();
});

it('menolak approval oleh salah satu penghitung stock count', function () {
    expect(fn () => AssertNotSameActor::enforce(7, 5, 6, 7))->toThrow(SegregationOfDutiesViolation::class)
        ->and(AssertNotSameActor::holds(8, 5, 6, 7))->toBeTrue();
});

it('model user, ID int, dan ID string yang sah dianggap pelaku yang sama', function () {
    expect(AssertNotSameActor::holds(actor(5), 5))->toBeFalse()
        ->and(AssertNotSameActor::holds(5, actor(5)))->toBeFalse()
        ->and(AssertNotSameActor::holds(5, '5'))->toBeFalse()
        ->and(AssertNotSameActor::holds('5', actor(5)))->toBeFalse();
});

it('gagal tertutup bila pembuat tidak diketahui', function (array $makers) {
    expect(fn () => AssertNotSameActor::enforce(5, ...$makers))
        ->toThrow(SegregationOfDutiesViolation::class, 'tidak diketahui');
})->with([
    'pembuat null' => [[null]],
    'tanpa pembuat' => [[]],
    'salah satu penghitung null' => [[6, null]],
]);

it('menolak ID yang formatnya bisa mengakali perbandingan', function (int|string $id) {
    expect(fn () => AssertNotSameActor::enforce(5, $id))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AssertNotSameActor::enforce($id, 5))->toThrow(InvalidArgumentException::class);
})->with(['05', '5 ', "5\n", ' 5', '1_0', '0x05', '+5', '-5', '5.0', '', 'abc', 0, -1]);

it('pelanggaran dirender sebagai 403', function () {
    Route::get('/_test/segregation', fn () => AssertNotSameActor::enforce(1, 1));

    expect(SegregationOfDutiesViolation::sameActor())->toBeInstanceOf(AuthorizationException::class);
    getJson('/_test/segregation')->assertForbidden();
});
