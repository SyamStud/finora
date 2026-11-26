<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use App\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Food & Drink', 'Transportation', 'Salary', 'Other']),
            'type' => fake()->randomElement(TransactionType::cases()),
            'icon' => 'tag',
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => TransactionType::Expense]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => TransactionType::Income]);
    }
}
