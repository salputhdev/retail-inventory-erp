<?php

declare(strict_types=1);

use App\Support\Money;
use App\Support\Quantity;
use Tests\Fixtures\Models\SampleLine;

it('membaca atribut sebagai value object', function () {
    $line = (new SampleLine)->setRawAttributes(['quantity' => '12.500', 'unit_cost' => '5000.0000']);

    expect($line->quantity)->toBeInstanceOf(Quantity::class)->and($line->quantity->toString())->toBe('12.500');
    expect($line->unit_cost)->toBeInstanceOf(Money::class)->and($line->unit_cost->toString())->toBe('5000.0000');
});

it('menyimpan sebagai string desimal ternormalisasi', function () {
    $line = new SampleLine;
    $line->quantity = 3;
    $line->unit_cost = '0.1';

    expect($line->getAttributes())->toBe(['quantity' => '3.000', 'unit_cost' => '0.1000']);

    $line->quantity = Quantity::of('1.5');
    expect($line->getAttributes()['quantity'])->toBe('1.500');
});

it('mengizinkan null untuk kolom nullable', function () {
    $line = (new SampleLine)->setRawAttributes(['quantity' => null, 'unit_cost' => null]);
    expect($line->quantity)->toBeNull()->and($line->unit_cost)->toBeNull();

    $line->quantity = null;
    expect($line->getAttributes()['quantity'])->toBeNull();
});

it('menolak float saat runtime', function () {
    // setAttribute() menerima mixed: jalur yang dilewati data tak tepercaya (mis. mass assignment dari request).
    $line = new SampleLine;
    expect(fn () => $line->setAttribute('quantity', 1.5))->toThrow(InvalidArgumentException::class, 'tidak menerima float');
    expect(fn () => $line->setAttribute('unit_cost', 0.1 + 0.2))->toThrow(InvalidArgumentException::class, 'tidak menerima float');
});

it('menolak tipe lain dan desimal berlebih', function () {
    $line = new SampleLine;
    expect(fn () => $line->setAttribute('quantity', Money::of('1')))->toThrow(InvalidArgumentException::class);
    expect(fn () => $line->setAttribute('quantity', '1.0001'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $line->setAttribute('quantity', true))->toThrow(InvalidArgumentException::class);
    expect(fn () => $line->fill(['unit_cost' => 1.5]))->toThrow(InvalidArgumentException::class);
});
