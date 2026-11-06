<?php

declare(strict_types=1);

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\Location;
use App\Domain\Identity\Models\User;
use App\Support\Authorization\BasePolicy;
use Illuminate\Auth\Access\Response;

/**
 * Location tidak ber-scope (ia sumber scope), jadi Policy inilah yang membatasinya (ISS-014):
 * user biasa hanya melihat lokasi yang di-assign (lainnya 404); kelola lokasi hanya `location.manage`.
 * Pemilih lokasi user memakai relasi User::locations(), bukan daftar semua lokasi.
 */
final class LocationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, Permission::LocationManage);
    }

    public function view(User $user, Location $location): Response
    {
        if ($this->hasPermission($user, Permission::LocationManage) || $this->inLocation($user, $location->id)) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::LocationManage);
    }

    public function update(User $user, Location $location): Response
    {
        return $this->manage($user, $location);
    }

    public function delete(User $user, Location $location): Response
    {
        return $this->manage($user, $location);
    }

    public function setCutover(User $user, Location $location): Response
    {
        return $this->manage($user, $location);
    }

    private function manage(User $user, Location $location): Response
    {
        if ($this->hasPermission($user, Permission::LocationManage)) {
            return Response::allow();
        }

        return $this->inLocation($user, $location->id) ? Response::deny() : Response::denyAsNotFound();
    }
}
