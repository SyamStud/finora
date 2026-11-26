<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_accounts_with_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/accounts', [
            'name' => 'BCA',
            'type' => 'bank',
            'currency' => 'IDR',
            'initial_balance' => 500000,
        ])->assertCreated()->assertJsonPath('data.balance', '500000.00');

        $this->getJson('/api/v1/accounts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'BCA');
    }

    public function test_balance_includes_income_expense_and_transfers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $source = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $destination = Account::factory()->for($user)->create(['initial_balance' => 0]);

        Transaction::factory()->for($user)->for($source)->income()->create(['amount' => 50000]);
        Transaction::factory()->for($user)->for($source)->expense()->create(['amount' => 20000]);

        $this->postJson('/api/v1/transfers', [
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 30000,
            'fee' => 5000,
            'transfer_date' => now()->toDateString(),
        ])->assertCreated();

        $data = collect($this->getJson('/api/v1/accounts')->assertOk()->json('data'))->keyBy('id');

        $this->assertSame('95000.00', $data[$source->id]['balance']);
        $this->assertSame('30000.00', $data[$destination->id]['balance']);
    }

    public function test_user_cannot_see_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherAccount = Account::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/accounts/'.$otherAccount->id)
            ->assertNotFound();
    }

    public function test_deleting_an_account_archives_it(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/accounts/'.$account->id)
            ->assertOk();

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'is_active' => false]);
    }
}
