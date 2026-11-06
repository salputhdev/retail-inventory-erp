<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\LocationFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Lokasi (toko/gudang). Sengaja TIDAK memakai BelongsToLocation: ini sumber scope itu sendiri.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $timezone
 * @property bool $is_active
 * @property Carbon|null $cutover_at
 */
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Mass assignment dibatasi eksplisit (F5). `cutover_at` (MG-03: transaksi POS sebelumnya diabaikan) dan `is_active`
     * sengaja TIDAK fillable: ubah lewat Action/method khusus yang bisa diaudit.
     */
    protected $fillable = ['code', 'name', 'timezone', 'opening_time', 'closing_time'];

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'cutover_at' => 'datetime',
        ];
    }

    public function activate(): void
    {
        $this->forceFill(['is_active' => true])->save();
    }

    public function deactivate(): void
    {
        $this->forceFill(['is_active' => false])->save();
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'location_user')->withTimestamps();
    }

    /** Ubah waktu (UTC di database) ke zona waktu lokasi untuk ditampilkan (I-01). */
    public function localize(DateTimeInterface|string $utc): CarbonImmutable
    {
        return CarbonImmutable::parse($utc, 'UTC')->setTimezone($this->timezone);
    }

    public function format(DateTimeInterface|string $utc, string $format = 'd/m/Y H:i'): string
    {
        return $this->localize($utc)->format($format);
    }

    /** Ubah waktu lokal lokasi (mis. input form) ke UTC untuk disimpan. */
    public function toUtc(CarbonInterface|string $local): CarbonImmutable
    {
        $instance = $local instanceof CarbonInterface
            ? CarbonImmutable::instance($local)->shiftTimezone($this->timezone)
            : CarbonImmutable::parse($local, $this->timezone);

        return $instance->setTimezone('UTC');
    }
}
