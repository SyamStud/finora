<?php

namespace Database\Factories;

use App\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Money Market Fund', 'Gold', 'BBCA']),
            'type' => fake()->randomElement(AssetType::cases()),
            'provider' => fake()->company(),
            'units' => fake()->randomFloat(6, 1, 1000),
            'average_buy_price' => fake()->randomFloat(2, 1000, 100_000),
            'current_price' => fake()->randomFloat(2, 1000, 100_000),
            'invested_amount' => fake()->randomFloat(2, 100_000, 10_000_000),
            'current_value' => fake()->randomFloat(2, 100_000, 10_000_000),
            'currency' => 'IDR',
            'notes' => null,
            'is_active' => true,
        ];
    }
}
