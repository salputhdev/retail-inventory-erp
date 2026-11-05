<?php

declare(strict_types=1);

use App\Support\Money;
use App\Support\Quantity;

it('0.1 + 0.2 menghasilkan tepat 0.3', function () {
    expect(Quantity::of('0.1')->plus('0.2')->toString())->toBe('0.300');
    expect(Money::of('0.1')->plus('0.2')->toString())->toBe('0.3000');
    expect(Quantity::of('0.1')->plus('0.2')->equals('0.3'))->toBeTrue();
});

it('menormalkan ke tepat 3 desimal', function (string|int $input, string $expected) {
    expect(Quantity::of($input)->toString())->toBe($expected);
})->with([
    ['5', '5.000'], [5, '5.000'], ['5.5', '5.500'], ['0', '0.000'], ['007.25', '7.250'],
    ['-1.5', '-1.500'], ['+2', '2.000'], ['-0', '0.000'], ['-0.000', '0.000'],
]);

it('menjumlah dan mengurangi dengan 3 desimal, termasuk negatif', function () {
    expect(Quantity::of('10.125')->plus('0.875')->toString())->toBe('11.000');
    expect(Quantity::of('1.000')->minus('2.500')->toString())->toBe('-1.500');
    expect(Quantity::of('-1.250')->plus('-1.250')->toString())->toBe('-2.500');
    expect(Quantity::of('-1.250')->minus('-1.250')->toString())->toBe('0.000');
    expect(Quantity::of('0.001')->minus('0.002')->toString())->toBe('-0.001');
});

it('mengalikan dan membulatkan half away from zero', function (string $a, string $factor, string $expected) {
    expect(Quantity::of($a)->times($factor)->toString())->toBe($expected);
})->with([
    ['2.000', '3', '6.000'],
    ['1.000', '0.0005', '0.001'],     // 0.0005 -> naik
    ['1.000', '0.0004', '0.000'],     // 0.0004 -> turun
    ['-1.000', '0.0005', '-0.001'],   // negatif: menjauhi nol
    ['-1.000', '0.0004', '0.000'],    // jadi nol, bukan -0
    ['10.000', '0.115', '1.150'],
    ['3.333', '3', '9.999'],
    ['1.000', '-1', '-1.000'],
]);

it('membandingkan nilai', function () {
    $a = Quantity::of('1.500');
    expect($a->compareTo('1.5'))->toBe(0);
    expect($a->equals('1.500'))->toBeTrue();
    expect($a->greaterThan('1.499'))->toBeTrue();
    expect($a->greaterThanOrEqual('1.5'))->toBeTrue();
    expect($a->lessThan('1.501'))->toBeTrue();
    expect($a->lessThanOrEqual('1.5'))->toBeTrue();
    expect($a->greaterThan('1.5'))->toBeFalse();
    expect(Quantity::of('-0.001')->lessThan(0))->toBeTrue();
});

it('mengenali nol, positif, negatif, abs, dan negasi', function () {
    expect(Quantity::zero()->isZero())->toBeTrue();
    expect(Quantity::of('-0.001')->isNegative())->toBeTrue();
    expect(Quantity::of('0.001')->isPositive())->toBeTrue();
    expect(Quantity::of('-2.500')->abs()->toString())->toBe('2.500');
    expect(Quantity::of('2.500')->negated()->toString())->toBe('-2.500');
    expect(Quantity::zero()->negated()->toString())->toBe('0.000');
});

it('immutable: operasi tidak mengubah instance asal', function () {
    $a = Quantity::of('1');
    $a->plus('5');
    expect($a->toString())->toBe('1.000');
});

it('menolak desimal melebihi skala (tidak membulatkan diam-diam)', function () {
    Quantity::of('1.0001');
})->throws(InvalidArgumentException::class, 'maksimum 3');

it('menolak input yang bukan angka desimal', function (string $bad) {
    Quantity::of($bad);
})->with(['', 'abc', '1,5', '1e3', '1.', '.5', '--1', '1 2', 'NaN'])->throws(InvalidArgumentException::class);

it('menolak float lewat tipe (strict_types)', function () {
    Quantity::of(0.5); // @phpstan-ignore argument.type
})->throws(TypeError::class);

it('menolak mencampur Quantity dengan Money', function () {
    Quantity::of(Money::of('1'));
})->throws(InvalidArgumentException::class, 'Tidak bisa mencampur');

it('menolak faktor yang bukan angka', function () {
    Quantity::of('1')->times('abc');
})->throws(InvalidArgumentException::class);

it('menolak nilai di luar numeric(14,3)', function () {
    Quantity::of('100000000000'); // 12 digit bulat
})->throws(OverflowException::class);

it('menerima nilai maksimum numeric(14,3) dan menolak melampauinya lewat operasi', function () {
    $max = Quantity::of('99999999999.999');
    expect($max->toString())->toBe('99999999999.999');
    $max->plus('0.001');
})->throws(OverflowException::class);

it('serialisasi: string, JSON, dan cast ke string', function () {
    $q = Quantity::of('12.5');
    expect((string) $q)->toBe('12.500');
    expect(json_encode(['qty' => $q]))->toBe('{"qty":"12.500"}');
});

it('contoh nyata: avg cost penerimaan (PRD US-1.05)', function () {
    // 24 pcs @ 5.000 -> nilai 120.000
    $value = Money::of('5000')->times(Quantity::of('24'));
    expect($value->toString())->toBe('120000.0000');
});
