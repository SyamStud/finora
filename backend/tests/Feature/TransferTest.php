<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_moves_money_without_affecting_income_or_expense(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $source = Account::factory()->for($user)->create(['initial_balance' => 500000]);
        $destination = Account::factory()->for($user)->create(['initial_balance' => 0]);

        $this->postJson('/api/v1/transfers', [
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 100000,
            'transfer_date' => now()->toDateString(),
        ])->assertCreated();

        $data = collect($this->getJson('/api/v1/accounts')->json('data'))->keyBy('id');

        $this->assertSame('400000.00', $data[$source->id]['balance']);
        $this->assertSame('100000.00', $data[$destination->id]['balance']);

        $this->getJson('/api/v1/transactions')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_transfer_fee_is_recorded_as_expense(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $source = Account::factory()->for($user)->create(['initial_balance' => 500000]);
        $destination = Account::factory()->for($user)->create(['initial_balance' => 0]);

        $this->postJson('/api/v1/transfers', [
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 100000,
            'fee' => 2500,
            'transfer_date' => now()->toDateString(),
        ])->assertCreated();

        $data = collect($this->getJson('/api/v1/accounts')->json('data'))->keyBy('id');
        $this->assertSame('397500.00', $data[$source->id]['balance']);
        $this->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Biaya transfer');
    }

    public function test_source_and_destination_must_differ(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/transfers', [
            'source_account_id' => $account->id,
            'destination_account_id' => $account->id,
            'amount' => 10000,
            'transfer_date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('source_account_id');
    }

    public function test_user_cannot_transfer_from_another_users_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $foreign = Account::factory()->create();
        $own = Account::factory()->for($user)->create();

        $this->postJson('/api/v1/transfers', [
            'source_account_id' => $foreign->id,
            'destination_account_id' => $own->id,
            'amount' => 10000,
            'transfer_date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('source_account_id');
    }
}
