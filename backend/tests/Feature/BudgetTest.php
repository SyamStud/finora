<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_usage_tracks_expenses_in_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->expense()->create();
        $account = Account::factory()->for($user)->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/budgets', [
            'category_id' => $category->id, 'amount' => 1000000,
            'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.usage.amount', '1000000.00');

        Transaction::factory()->for($user)->for($account)->for($category)->expense()->create([
            'amount' => 250000, 'transaction_date' => now(),
        ]);

        $this->getJson('/api/v1/budgets')
            ->assertOk()
            ->assertJsonPath('data.0.usage.used', '250000.00')
            ->assertJsonPath('data.0.usage.remaining', '750000.00')
            ->assertJsonPath('data.0.usage.percentage', 25);
    }
}
