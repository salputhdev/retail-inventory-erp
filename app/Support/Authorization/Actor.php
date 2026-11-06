<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Domain\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/** Pelaku aksi sensitif diambil dari sesi login, bukan dari parameter pemanggil (ISS-017). */
final class Actor
{
    /** @throws AuthorizationException */
    public static function current(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new AuthorizationException('Aksi ini membutuhkan user yang login.');
        }

        return $user;
    }
}
