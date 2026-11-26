<?php

namespace Database\Factories;

use App\InputSource;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'type' => fake()->randomElement(TransactionType::cases()),
            'amount' => fake()->randomFloat(2, 1000, 1_000_000),
            'description' => fake()->sentence(3),
            'notes' => null,
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'input_source' => InputSource::Manual,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => TransactionType::Income]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => TransactionType::Expense]);
    }
}
