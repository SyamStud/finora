<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'source_account_id' => Account::factory()->for($user),
            'destination_account_id' => Account::factory()->for($user),
            'amount' => fake()->randomFloat(2, 10_000, 1_000_000),
            'fee' => 0,
            'description' => fake()->sentence(2),
            'transfer_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
