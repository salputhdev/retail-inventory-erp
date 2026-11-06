<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Contracts\HasLocationAccess;
use App\Support\Exceptions\LocationNotAllowed;
use App\Support\Exceptions\MissingLocationContext;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Menentukan lokasi mana yang boleh dilihat/ditulis saat ini (ADR-003, AR-07).
 *
 * Urutan: (1) konteks eksplisit dari run() (job, command, pilihan lokasi di UI), (2) lokasi milik user yang login.
 * Tanpa keduanya: GAGAL TERTUTUP (MissingLocationContext). Didaftarkan sebagai `scoped`, jadi di-reset
 * di setiap request dan setiap job queue; tidak ada kebocoran antar eksekusi.
 */
final class LocationContext
{
    /** @var list<list<int>> */
    private array $stack = [];

    /** @var array<int|string, list<int>> cache akses per user selama satu request/job */
    private array $userCache = [];

    private int $systemDepth = 0;

    /**
     * Jalankan $callback dalam konteks lokasi eksplisit. Bila ada user login, lokasi harus bagian dari aksesnya.
     *
     * @param  int|list<int>  $locationIds
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(int|array $locationIds, Closure $callback): mixed
    {
        $ids = array_values(array_unique(array_map(LocationWriteGuard::normalize(...), (array) $locationIds)));

        foreach ($ids as $id) {
            $this->assertAllowed($id);
        }

        $this->stack[] = $ids;
        try {
            return $callback();
        } finally {
            array_pop($this->stack);
        }
    }

    /**
     * Jalankan $callback sebagai proses SISTEM (seeder, command, job terjadwal): boleh menulis location_id eksplisit
     * tanpa konteks user. Hanya di console; di request HTTP ditolak. Pembacaan tetap lewat run()/withoutLocationScope().
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function asSystem(Closure $callback): mixed
    {
        if (! app()->runningInConsole()) {
            throw LocationNotAllowed::systemOnly();
        }

        $this->systemDepth++;
        try {
            return $callback();
        } finally {
            $this->systemDepth--;
        }
    }

    public function isSystem(): bool
    {
        return $this->systemDepth > 0;
    }

    /**
     * Pastikan principal saat ini boleh memakai lokasi ini. Dipanggil oleh run() dan saat men-dispatch job (F2/F3):
     *  - user login yang HasLocationAccess: lokasi harus termasuk akses;
     *  - principal login lain (mis. token API non-User): ditolak;
     *  - tanpa login: hanya diizinkan di console (scheduler/command); di request HTTP guest ditolak.
     *
     * @throws LocationNotAllowed
     */
    public function assertAllowed(int $locationId): void
    {
        $user = $this->principal();

        if ($user === null) {
            if (app()->runningInConsole()) {
                return;
            }
            throw LocationNotAllowed::for($locationId);
        }
        if (! $user instanceof HasLocationAccess) {
            throw LocationNotAllowed::unknownPrincipal();
        }
        if (! in_array($locationId, $this->allowedFor($user), true)) {
            throw LocationNotAllowed::for($locationId);
        }
    }

    /**
     * Lokasi yang berlaku sekarang. Bisa kosong (user tanpa lokasi = tidak melihat apa pun).
     *
     * @return list<int>
     *
     * @throws MissingLocationContext
     */
    public function ids(): array
    {
        if ($this->stack !== []) {
            return $this->stack[array_key_last($this->stack)];
        }

        $user = Auth::user();
        if ($user instanceof HasLocationAccess) {
            return $this->allowedFor($user);
        }

        throw MissingLocationContext::make();
    }

    public function forgetUserCache(): void
    {
        $this->userCache = [];
    }

    /**
     * Principal yang sedang login pada guard default. Tipenya sengaja `?object`: guard lain (mis. token API)
     * bisa mengembalikan principal yang BUKAN User, dan itu harus ditolak, bukan dianggap mustahil.
     */
    private function principal(): ?object
    {
        return Auth::user();
    }

    /** @return list<int> */
    private function allowedFor(HasLocationAccess $user): array
    {
        $key = method_exists($user, 'getAuthIdentifier') ? (string) $user->getAuthIdentifier() : spl_object_id($user);

        return $this->userCache[$key] ??= $user->locationIds();
    }
}
