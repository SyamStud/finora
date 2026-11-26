<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Pocket;
use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PocketTest extends TestCase
{
    use RefreshDatabase;

    public function test_pocket_belongs_to_account_and_starts_empty(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $this->actingAs($user);

        $this->postJson('/api/v1/accounts/'.$account->id.'/pockets', [
            'name' => 'Jajan', 'color' => '#f97316',
        ])->assertCreated()->assertJsonPath('data.name', 'Jajan');

        $this->getJson('/api/v1/accounts/'.$account->id.'/pockets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.balance', '0.00');
    }

    public function test_allocation_from_base_to_pocket_does_not_change_account_total(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $pocket = Pocket::factory()->for($user)->for($account)->create();
        $balance = app(AccountBalanceService::class);
        $this->actingAs($user);

        $this->assertSame('100000.00', $balance->forAccount($account));

        $this->postJson('/api/v1/pockets/allocate', [
            'account_id' => $account->id,
            'source_pocket_id' => null,
            'destination_pocket_id' => $pocket->id,
            'amount' => 30000,
            'allocated_at' => now()->toDateString(),
        ])->assertCreated();

        $this->assertSame('70000.00', $balance->baseBalance($account->refresh()));
        $this->assertSame('30000.00', $balance->forPocket($pocket));
        // Uang hanya berpindah: total rekening tetap.
        $this->assertSame('100000.00', $balance->forAccount($account));
    }

    public function test_expense_from_pocket_lowers_pocket_and_account_but_not_base(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $pocket = Pocket::factory()->for($user)->for($account)->create();
        $category = Category::factory()->for($user)->expense()->create();
        $balance = app(AccountBalanceService::class);
        $this->actingAs($user);

        $this->postJson('/api/v1/pockets/allocate', [
            'account_id' => $account->id,
            'destination_pocket_id' => $pocket->id,
            'amount' => 30000,
            'allocated_at' => now()->toDateString(),
        ])->assertCreated();

        $this->postJson('/api/v1/transactions', [
            'type' => 'expense',
            'amount' => 5000,
            'account_id' => $account->id,
            'pocket_id' => $pocket->id,
            'category_id' => $category->id,
            'description' => 'Beli kopi',
            'transaction_date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.pocket.name', $pocket->name);

        $this->assertSame('25000.00', $balance->forPocket($pocket->refresh()));
        $this->assertSame('70000.00', $balance->baseBalance($account->refresh()));
        $this->assertSame('95000.00', $balance->forAccount($account));
    }

    public function test_allocation_between_pockets_keeps_account_total(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $jajan = Pocket::factory()->for($user)->for($account)->create(['name' => 'Jajan']);
        $tabungan = Pocket::factory()->for($user)->for($account)->create(['name' => 'Tabungan']);
        $balance = app(AccountBalanceService::class);
        $this->actingAs($user);

        foreach ([$jajan, $tabungan] as $pocket) {
            $this->postJson('/api/v1/pockets/allocate', [
                'account_id' => $account->id,
                'destination_pocket_id' => $pocket->id,
                'amount' => 20000,
                'allocated_at' => now()->toDateString(),
            ])->assertCreated();
        }

        $this->postJson('/api/v1/pockets/allocate', [
            'account_id' => $account->id,
            'source_pocket_id' => $jajan->id,
            'destination_pocket_id' => $tabungan->id,
            'amount' => 5000,
            'allocated_at' => now()->toDateString(),
        ])->assertCreated();

        $this->assertSame('15000.00', $balance->forPocket($jajan->refresh()));
        $this->assertSame('25000.00', $balance->forPocket($tabungan->refresh()));
        $this->assertSame('100000.00', $balance->forAccount($account->refresh()));
    }

    public function test_allocation_rejects_same_source_and_destination(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $pocket = Pocket::factory()->for($user)->for($account)->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/pockets/allocate', [
            'account_id' => $account->id,
            'source_pocket_id' => $pocket->id,
            'destination_pocket_id' => $pocket->id,
            'amount' => 1000,
            'allocated_at' => now()->toDateString(),
        ])->assertUnprocessable();
    }

    public function test_transaction_rejects_pocket_from_another_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $otherAccount = Account::factory()->for($user)->create();
        $pocket = Pocket::factory()->for($user)->for($otherAccount)->create();
        $category = Category::factory()->for($user)->expense()->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/transactions', [
            'type' => 'expense', 'amount' => 1000,
            'account_id' => $account->id, 'pocket_id' => $pocket->id,
            'category_id' => $category->id, 'description' => 'Salah pocket',
            'transaction_date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('pocket_id');
    }

    public function test_pockets_are_isolated_per_user(): void
    {
        $user = User::factory()->create();
        $foreignPocket = Pocket::factory()->create();
        $this->actingAs($user);

        $this->getJson('/api/v1/pockets/'.$foreignPocket->id)->assertNotFound();
    }

    public function test_dashboard_net_worth_includes_pockets_once(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $pocket = Pocket::factory()->for($user)->for($account)->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/pockets/allocate', [
            'account_id' => $account->id,
            'destination_pocket_id' => $pocket->id,
            'amount' => 20000,
            'allocated_at' => now()->toDateString(),
        ])->assertCreated();

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('summary.total_cash', '100000.00')
            ->assertJsonPath('summary.net_worth', '100000.00');
    }

    public function test_account_detail_exposes_base_and_pocket_breakdown(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 100000]);
        $pocket = Pocket::factory()->for($user)->for($account)->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/pockets/allocate', [
            'account_id' => $account->id,
            'destination_pocket_id' => $pocket->id,
            'amount' => 20000,
            'allocated_at' => now()->toDateString(),
        ])->assertCreated();

        $this->getJson('/api/v1/accounts/'.$account->id)
            ->assertOk()
            ->assertJsonPath('data.base_balance', '80000.00')
            ->assertJsonPath('data.pockets_balance', '20000.00')
            ->assertJsonPath('data.balance', '100000.00')
            ->assertJsonCount(1, 'data.pockets');
    }
}
