<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\PendingTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationIngestTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_notification_is_parsed_into_pending_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['name' => 'BCA']);
        $category = Category::factory()->for($user)->expense()->create(['name' => 'Food & Drink']);
        $this->actingAs($user);

        $this->postJson('/api/v1/notifications/ingest', [
            'raw_text' => 'Beli kopi 25rb pakai BCA',
            'package_name' => 'com.bca.mobile',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.amount', '25000.00')
            ->assertJsonPath('data.account.name', 'BCA')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseCount('pending_transactions', 1);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_income_notification_with_juta_amount(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->create(['name' => 'BCA']);
        $this->actingAs($user);

        $this->postJson('/api/v1/notifications/ingest', [
            'raw_text' => 'Gaji 5 juta masuk BCA hari ini',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.amount', '5000000.00');
    }

    public function test_same_notification_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = ['raw_text' => 'Bayar listrik 250 ribu dari BCA'];
        $this->postJson('/api/v1/notifications/ingest', $payload)->assertCreated();
        $this->postJson('/api/v1/notifications/ingest', $payload)->assertCreated();

        $this->assertDatabaseCount('pending_transactions', 1);
    }

    public function test_pending_can_be_edited_then_approved(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['name' => 'BCA']);
        $category = Category::factory()->for($user)->expense()->create(['name' => 'Food & Drink']);
        $this->actingAs($user);

        $pending = PendingTransaction::factory()->for($user)->create([
            'account_id' => null, 'account_name' => null,
            'category_id' => null, 'amount' => 25_000,
        ]);

        $this->patchJson('/api/v1/pending-transactions/'.$pending->id, [
            'account_id' => $account->id,
            'category_id' => $category->id,
        ])->assertOk();

        $this->postJson('/api/v1/pending-transactions/'.$pending->id.'/approve')
            ->assertCreated()
            ->assertJsonPath('data.amount', '25000.00');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'input_source' => 'import',
        ]);
        $this->assertDatabaseHas('pending_transactions', [
            'id' => $pending->id, 'status' => 'approved',
        ]);
    }

    public function test_approve_requires_account_and_amount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pending = PendingTransaction::factory()->for($user)->create([
            'account_id' => null, 'amount' => null,
        ]);

        $this->postJson('/api/v1/pending-transactions/'.$pending->id.'/approve')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account_id');
    }

    public function test_pending_can_be_rejected(): void
    {
        $user = User::factory()->create();
        $pending = PendingTransaction::factory()->for($user)->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/pending-transactions/'.$pending->id.'/reject')->assertOk();

        $this->assertDatabaseHas('pending_transactions', [
            'id' => $pending->id, 'status' => 'rejected',
        ]);
        $this->assertDatabaseCount('transactions', 0);
    }
}
