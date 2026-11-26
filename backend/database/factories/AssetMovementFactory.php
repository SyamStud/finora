<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetMovement>
 */
class AssetMovementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'asset_id' => Asset::factory()->for($user),
            'type' => 'topup',
            'amount' => fake()->randomFloat(2, 100_000, 5_000_000),
            'units' => fake()->randomFloat(6, 50, 2500),
            'nav' => fake()->randomFloat(2, 1500, 2100),
            'movement_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'note' => null,
        ];
    }
}
