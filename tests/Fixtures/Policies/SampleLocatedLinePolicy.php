<?php

declare(strict_types=1);

namespace Tests\Fixtures\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Support\Authorization\BasePolicy;
use Illuminate\Auth\Access\Response;
use Tests\Fixtures\Models\SampleLocatedLine;

/** Policy contoh untuk model ber-lokasi, seperti yang akan dipakai dokumen stok (T2.04). */
final class SampleLocatedLinePolicy extends BasePolicy
{
    public function update(User $user, SampleLocatedLine $line): Response
    {
        return $this->permitInLocation($user, Permission::GoodsReceiptPost, $line->location_id);
    }
}
