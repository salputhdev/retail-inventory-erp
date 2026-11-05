<?php

declare(strict_types=1);

use App\Support\Money;

it('menormalkan ke tepat 4 desimal', function (string|int $input, string $expected) {
    expect(Money::of($input)->toString())->toBe($expected);
})->with([['5000', '5000.0000'], [5000, '5000.0000'], ['0.5', '0.5000'], ['-12.34', '-12.3400'], ['0.0001', '0.0001']]);

it('menjumlah, mengurangi, dan membandingkan', function () {
    expect(Money::of('0.0001')->plus('0.0002')->toString())->toBe('0.0003');
    expect(Money::of('10')->minus('10.0001')->toString())->toBe('-0.0001');
    expect(Money::of('5000')->greaterThan('4999.9999'))->toBeTrue();
});

it('mengalikan dengan pembulatan 4 desimal', function () {
    expect(Money::of('1')->times('0.00005')->toString())->toBe('0.0001');
    expect(Money::of('1')->times('0.00004')->toString())->toBe('0.0000');
    expect(Money::of('-1')->times('0.00005')->toString())->toBe('-0.0001');
    expect(Money::of('19999.99')->times('1.1')->toString())->toBe('21999.9890');
});

it('menolak lebih dari 4 desimal dan float', function () {
    expect(fn () => Money::of('1.00001'))->toThrow(InvalidArgumentException::class);
    expect(fn () => Money::of(1.5))->toThrow(TypeError::class); // @phpstan-ignore argument.type
});

it('menolak nilai di luar numeric(14,4)', function () {
    expect(fn () => Money::of('10000000000'))->toThrow(OverflowException::class); // 11 digit bulat
    expect(Money::of('9999999999.9999')->toString())->toBe('9999999999.9999');
});

it('rata-rata tertimbang: hasil sama persis tanpa float (ADR-008)', function () {
    // stok 10 @ 100, masuk 5 @ 130 -> (1000 + 650) / 15 = 110
    $value = Money::of('100')->times('10')->plus(Money::of('130')->times('5'));
    expect($value->toString())->toBe('1650.0000');
    expect($value->times('1')->toString())->toBe('1650.0000');
});
