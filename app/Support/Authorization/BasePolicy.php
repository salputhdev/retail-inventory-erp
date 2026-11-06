<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Policies\PermissionGate;
use Illuminate\Auth\Access\Response;

/**
 * Dasar Policy: izin per aksi (PRD §4.3) dan scope lokasi (R-01, ADR-003).
 * Lokasi diperiksa sebelum izin, jadi data lokasi lain selalu 404 dan keberadaannya tidak bocor lewat 403.
 */
abstract class BasePolicy
{
    protected function hasPermission(User $user, Permission $permission): bool
    {
        return PermissionGate::allows($user, $permission);
    }

    protected function inLocation(User $user, int $locationId): bool
    {
        return in_array($locationId, $user->locationIds(), true);
    }

    protected function permitInLocation(User $user, Permission $permission, int $locationId): Response
    {
        if (! $this->inLocation($user, $locationId)) {
            return Response::denyAsNotFound();
        }

        return $this->hasPermission($user, $permission) ? Response::allow() : Response::deny();
    }
}
