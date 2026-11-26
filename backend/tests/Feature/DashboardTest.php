<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summarizes_net_worth_and_cash_flow(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $income = Category::factory()->for($user)->income()->create();
        $expense = Category::factory()->for($user)->expense()->create();
        $this->actingAs($user);

        Transaction::factory()->for($user)->for($account)->for($income)->income()->create([
            'amount' => 500000, 'transaction_date' => now(),
        ]);
        Transaction::factory()->for($user)->for($account)->for($expense)->expense()->create([
            'amount' => 100000, 'transaction_date' => now(),
        ]);
        Asset::factory()->for($user)->create(['current_value' => 250000]);

        $this->getJson('/api/v1/dashboard?period=month')
            ->assertOk()
            ->assertJsonPath('summary.income', '500000.00')
            ->assertJsonPath('summary.expense', '100000.00')
            ->assertJsonPath('summary.net_cash_flow', '400000.00')
            ->assertJsonPath('summary.total_assets', '250000.00')
            ->assertJsonPath('summary.total_cash', '500000.00')
            ->assertJsonPath('summary.net_worth', '750000.00')
            ->assertJsonPath('summary.savings_rate', 80);
    }

    public function test_dashboard_recent_transactions_are_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Transaction::factory()->create();

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonCount(0, 'recent_transactions');
    }
}
