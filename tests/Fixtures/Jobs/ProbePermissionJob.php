<?php

declare(strict_types=1);

namespace Tests\Fixtures\Jobs;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Gate;

/** Job contoh: mencatat apakah user punya izin saat job berjalan di worker. */
final class ProbePermissionJob implements ShouldQueue
{
    use Queueable;

    public static ?bool $allowed = null;

    public function __construct(public int $userId, public Permission $permission) {}

    public function handle(): void
    {
        self::$allowed = Gate::forUser(User::query()->findOrFail($this->userId))->allows($this->permission);
    }
}
