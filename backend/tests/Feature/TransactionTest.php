<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_record_income_and_expense(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $income = Category::factory()->for($user)->income()->create();
        $expense = Category::factory()->for($user)->expense()->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/transactions', [
            'type' => 'income', 'amount' => 50000, 'account_id' => $account->id,
            'category_id' => $income->id, 'description' => 'Gaji',
            'transaction_date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.type', 'income');

        $this->postJson('/api/v1/transactions', [
            'type' => 'expense', 'amount' => 25000, 'account_id' => $account->id,
            'category_id' => $expense->id, 'description' => 'Makan',
            'transaction_date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.type', 'expense');

        $this->assertDatabaseCount('transactions', 2);
        $this->getJson('/api/v1/accounts')->assertJsonPath('data.0.balance', '125000.00');
    }

    public function test_category_type_must_match_transaction_type(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $expense = Category::factory()->for($user)->expense()->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/transactions', [
            'type' => 'income', 'amount' => 10000, 'account_id' => $account->id,
            'category_id' => $expense->id, 'description' => 'Salah kategori',
            'transaction_date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_transaction_can_be_edited_and_deleted(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $expense = Category::factory()->for($user)->expense()->create();
        $this->actingAs($user);

        $transaction = Transaction::factory()->for($user)->for($account)->for($expense)->expense()->create([
            'amount' => 30000,
        ]);

        $this->patchJson('/api/v1/transactions/'.$transaction->id, ['amount' => 10000])
            ->assertOk()->assertJsonPath('data.amount', '10000.00');

        $this->getJson('/api/v1/accounts')->assertJsonPath('data.0.balance', '90000.00');

        $this->deleteJson('/api/v1/transactions/'.$transaction->id)->assertOk();
        $this->getJson('/api/v1/accounts')->assertJsonPath('data.0.balance', '100000.00');
    }

    public function test_transactions_can_be_filtered(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $expense = Category::factory()->for($user)->expense()->create();
        $this->actingAs($user);

        Transaction::factory()->count(3)->for($user)->for($account)->for($expense)->expense()->create([
            'description' => 'Kopi', 'transaction_date' => now()->subDay(),
        ]);
        Transaction::factory()->for($user)->for($account)->income()->create([
            'description' => 'Gaji', 'transaction_date' => now(),
        ]);

        $this->getJson('/api/v1/transactions?type=expense')
            ->assertOk()->assertJsonCount(3, 'data');

        $this->getJson('/api/v1/transactions?search=Kopi')
            ->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_access_other_users_transaction(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/transactions/'.Transaction::factory()->create()->id)
            ->assertNotFound();
    }
}
