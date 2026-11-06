<?php

namespace Database\Factories;

use App\Domain\Identity\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'LOC'.fake()->unique()->numerify('###'),
            'name' => 'Toko '.fake()->unique()->word(),
            'timezone' => 'Asia/Jakarta',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'is_active' => true,
        ];
    }
}
