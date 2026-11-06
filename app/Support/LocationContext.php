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
        $ids = array_values(array_unique(array_map('intval', (array) $locationIds)));

        $user = Auth::user();
        if ($user instanceof HasLocationAccess) {
            $allowed = $this->allowedFor($user);
            foreach ($ids as $id) {
                if (! in_array($id, $allowed, true)) {
                    throw LocationNotAllowed::for($id);
                }
            }
        }

        $this->stack[] = $ids;
        try {
            return $callback();
        } finally {
            array_pop($this->stack);
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

    /** Ada konteks (eksplisit atau user) tanpa melempar? */
    public function hasContext(): bool
    {
        return $this->stack !== [] || Auth::user() instanceof HasLocationAccess;
    }

    public function forgetUserCache(): void
    {
        $this->userCache = [];
    }

    /** @return list<int> */
    private function allowedFor(HasLocationAccess $user): array
    {
        $key = method_exists($user, 'getAuthIdentifier') ? (string) $user->getAuthIdentifier() : spl_object_id($user);

        return $this->userCache[$key] ??= $user->locationIds();
    }
}
