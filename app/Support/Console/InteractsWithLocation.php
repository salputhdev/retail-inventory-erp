<?php

declare(strict_types=1);

namespace App\Support\Console;

use App\Domain\Identity\Models\Location;
use App\Support\LocationContext;
use Closure;

/**
 * Untuk command: wajib menyebut lokasi eksplisit lewat --location=KODE (tanpa default ke "semua").
 * Tambahkan `{--location= : Kode lokasi}` ke signature dan panggil runInLocation().
 */
trait InteractsWithLocation
{
    /**
     * @template T
     *
     * @param  Closure(int $locationId): T  $callback
     * @return T|null null bila lokasi tidak diberikan/tidak ditemukan (pesan error sudah ditampilkan)
     */
    protected function runInLocation(Closure $callback): mixed
    {
        $code = $this->option('location');
        if (! is_string($code) || $code === '') {
            $this->error('Wajib menyebut lokasi: --location=KODE.');

            return null;
        }

        $location = Location::query()->where('code', $code)->first();
        if ($location === null) {
            $this->error("Lokasi \"{$code}\" tidak ditemukan.");

            return null;
        }

        return app(LocationContext::class)->run($location->id, fn () => $callback($location->id));
    }
}
