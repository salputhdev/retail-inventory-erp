<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Support\BelongsToLocation;
use Illuminate\Database\Eloquent\Model;

/**
 * Model transaksional contoh (tabel dibuat di test).
 *
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property string|null $occurred_at
 */
class SampleLocatedLine extends Model
{
    use BelongsToLocation;

    protected $table = 'sample_located_lines';

    protected $guarded = [];
}
