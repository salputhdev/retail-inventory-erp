<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use OverflowException;
use Stringable;

/**
 * Bilangan desimal presisi tetap berbasis string (AR-06): tidak pernah float.
 *
 * Subclass menentukan SCALE (jumlah desimal) dan PRECISION (total digit, sama dengan kolom numeric(p,s)).
 * Objek immutable; setiap operasi mengembalikan instance baru.
 */
abstract class Decimal implements JsonSerializable, Stringable
{
    protected const SCALE = 0;

    protected const PRECISION = 0;

    final protected function __construct(private readonly string $value) {}

    /** Buat dari string/int. Float ditolak oleh tipe; desimal melebihi SCALE ditolak (tidak membulatkan diam-diam). */
    public static function of(string|int|self $value): static
    {
        if ($value instanceof self) {
            if (! $value instanceof static) {
                throw new InvalidArgumentException(sprintf('Tidak bisa mencampur %s dengan %s.', $value::class, static::class));
            }
            $value = $value->value;
        }

        $value = trim((string) $value);

        if (! preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException(sprintf('Bukan angka desimal yang valid: "%s".', $value));
        }

        $decimals = str_contains($value, '.') ? strlen(explode('.', $value)[1]) : 0;
        if ($decimals > static::SCALE) {
            throw new InvalidArgumentException(sprintf(
                'Nilai "%s" punya %d desimal; maksimum %d. Bulatkan secara eksplisit lewat operasi aritmetika.',
                $value, $decimals, static::SCALE,
            ));
        }

        return new static(static::normalize($value));
    }

    /** Untuk batas tak bertipe (cast Eloquent): hanya Decimal sejenis, string, atau int; float/bool/lainnya ditolak. */
    public static function fromMixed(mixed $value, string $label = 'nilai'): static
    {
        if (! is_string($value) && ! is_int($value) && ! $value instanceof static) {
            throw new InvalidArgumentException(sprintf('Atribut "%s" tidak menerima %s; pakai %s, string, atau int.', $label, get_debug_type($value), class_basename(static::class)));
        }

        return static::of($value);
    }

    public static function zero(): static
    {
        return static::of(0);
    }

    public function plus(string|int|self $other): static
    {
        return new static(static::normalize(bcadd($this->value, static::of($other)->value, static::SCALE)));
    }

    public function minus(string|int|self $other): static
    {
        return new static(static::normalize(bcsub($this->value, static::of($other)->value, static::SCALE)));
    }

    /**
     * Kali dengan faktor (string/int/Decimal lain). Hasil dibulatkan half away from zero ke SCALE.
     * Faktor boleh punya desimal berapa pun (mis. diskon "0.115").
     */
    public function times(string|int|self $factor): static
    {
        $factor = $factor instanceof self ? $factor->value : trim((string) $factor);
        if (! preg_match('/^[+-]?\d+(\.\d+)?$/', $factor)) {
            throw new InvalidArgumentException(sprintf('Faktor bukan angka desimal yang valid: "%s".', $factor));
        }

        return new static(static::normalize(static::round(bcmul($this->value, $factor, 24))));
    }

    public function negated(): static
    {
        return new static(static::normalize(bcmul($this->value, '-1', static::SCALE)));
    }

    public function abs(): static
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    public function compareTo(string|int|self $other): int
    {
        return bccomp($this->value, static::of($other)->value, static::SCALE);
    }

    public function equals(string|int|self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function greaterThan(string|int|self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function greaterThanOrEqual(string|int|self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    public function lessThan(string|int|self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function lessThanOrEqual(string|int|self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    public function isZero(): bool
    {
        return $this->compareTo(0) === 0;
    }

    public function isNegative(): bool
    {
        return $this->compareTo(0) < 0;
    }

    public function isPositive(): bool
    {
        return $this->compareTo(0) > 0;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /** Bulatkan half away from zero ke SCALE (bcmath hanya memotong). */
    protected static function round(string $value): string
    {
        $half = '0.'.str_repeat('0', static::SCALE).'5';

        return bccomp($value, '0', 24) >= 0
            ? bcadd($value, $half, static::SCALE)
            : bcsub($value, $half, static::SCALE);
    }

    /** Normalisasi ke tepat SCALE desimal, tanpa "-0", dan pastikan muat di numeric(PRECISION, SCALE). */
    protected static function normalize(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$int, $frac] = array_pad(explode('.', $value, 2), 2, '');

        $int = ltrim($int, '0') === '' ? '0' : ltrim($int, '0');
        $frac = str_pad(substr($frac, 0, static::SCALE), static::SCALE, '0');

        if (strlen($int) > static::PRECISION - static::SCALE) {
            throw new OverflowException(sprintf(
                'Nilai melebihi numeric(%d,%d): maksimum %d digit bulat.',
                static::PRECISION, static::SCALE, static::PRECISION - static::SCALE,
            ));
        }

        $result = $int.(static::SCALE > 0 ? '.'.$frac : '');

        return ($negative && trim($int.$frac, '0') !== '') ? '-'.$result : $result;
    }
}
