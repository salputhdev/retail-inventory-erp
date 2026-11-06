<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * Hash palsu untuk login email tak dikenal: Hash::check() tetap berbiaya sama dengan user asli (anti-enumerasi
 * lewat waktu respons). Dibuat SEKALI dan disimpan di cache, bukan dihitung per request: di PHP-FPM `static`
 * hilang tiap request, sehingga Hash::make() ikut berjalan hanya untuk email tak dikenal dan malah menambah selisih waktu.
 * Kunci memuat jumlah ronde bcrypt, jadi hash baru dibuat otomatis bila BCRYPT_ROUNDS berubah.
 */
final class DummyHash
{
    public static function value(): string
    {
        $key = 'auth.dummy-hash.'.(int) config('hashing.bcrypt.rounds');

        try {
            return (string) Cache::rememberForever($key, fn (): string => Hash::make(Str::random(40)));
        } catch (Throwable) {
            return Hash::make(Str::random(40)); // cache tak terjangkau: tetap berbiaya sama, hanya lebih lambat
        }
    }
}
