<?php

namespace Database\Factories;

use App\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['BCA', 'Mandiri', 'Cash', 'GoPay', 'Wallet Jajan']),
            'type' => fake()->randomElement(AccountType::cases()),
            'currency' => 'IDR',
            'initial_balance' => fake()->randomFloat(2, 0, 10_000_000),
            'icon' => 'wallet',
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}
