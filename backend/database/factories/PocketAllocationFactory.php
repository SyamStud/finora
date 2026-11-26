<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Pocket;
use App\Models\PocketAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PocketAllocation>
 */
class PocketAllocationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'account_id' => Account::factory()->for($user),
            'source_pocket_id' => null,
            'destination_pocket_id' => Pocket::factory()->for($user),
            'amount' => fake()->randomFloat(2, 10_000, 1_000_000),
            'note' => null,
            'allocated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
