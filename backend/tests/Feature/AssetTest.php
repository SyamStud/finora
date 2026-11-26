<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_values_and_profit_are_derived(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/assets', [
            'name' => 'Money Market Fund', 'type' => 'mutual_fund',
            'units' => 1250, 'average_buy_price' => 1200, 'current_price' => 1250,
        ])->assertCreated()
            ->assertJsonPath('data.invested_amount', '1500000.00')
            ->assertJsonPath('data.current_value', '1562500.00')
            ->assertJsonPath('data.profit_loss', '62500.00')
            ->assertJsonPath('data.return_percentage', 4.17);
    }

    public function test_valuation_updates_asset_and_keeps_history(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create([
            'units' => 100, 'current_price' => 1000, 'current_value' => 100000,
        ]);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets/'.$asset->id.'/valuations', [
            'unit_price' => 1200, 'valuation_date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.total_value', '120000.00');

        $this->getJson('/api/v1/assets/'.$asset->id)
            ->assertJsonPath('data.current_price', '1200.00')
            ->assertJsonPath('data.current_value', '120000.00');

        $this->getJson('/api/v1/assets/'.$asset->id.'/valuations')
            ->assertOk()->assertJsonCount(1, 'data');
    }
}
