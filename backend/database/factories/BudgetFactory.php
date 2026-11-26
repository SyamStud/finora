<?php

namespace Database\Factories;

use App\BudgetPeriod;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->expense(),
            'amount' => fake()->randomFloat(2, 500_000, 5_000_000),
            'period' => BudgetPeriod::Monthly,
            'start_date' => now()->startOfMonth(),
            'end_date' => null,
        ];
    }
}
