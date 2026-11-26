<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetValuation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetValuation>
 */
class AssetValuationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'valuation_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'unit_price' => fake()->randomFloat(2, 1000, 100_000),
            'total_value' => fake()->randomFloat(2, 100_000, 10_000_000),
        ];
    }
}
