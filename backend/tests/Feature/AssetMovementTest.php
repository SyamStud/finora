<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssetMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Http::fake([
            'bibit.id/*' => Http::response($this->nextData(2000.0, '2026-11-22')),
        ]);
    }

    public function test_top_up_uses_nav_to_convert_amount_into_units(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create([
            'type' => 'mutual_fund',
            'units' => 100,
            'average_buy_price' => 1800,
            'invested_amount' => 180_000,
            'current_price' => 1800,
            'current_value' => 180_000,
        ]);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets/'.$asset->id.'/top-up', ['amount' => 200_000])
            ->assertOk()
            ->assertJsonPath('data.units', '200.000000')
            ->assertJsonPath('data.current_price', '2000.00')
            ->assertJsonPath('data.current_value', '400000.00')
            ->assertJsonPath('data.invested_amount', '380000.00');

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'type' => 'topup',
            'amount' => 200_000,
            'units' => 100,
            'nav' => 2000,
        ]);
    }

    public function test_withdraw_recalculates_units_and_value(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create([
            'type' => 'mutual_fund',
            'units' => 200,
            'average_buy_price' => 1800,
            'invested_amount' => 360_000,
            'current_price' => 2000,
            'current_value' => 400_000,
        ]);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets/'.$asset->id.'/withdraw', ['amount' => 100_000])
            ->assertOk()
            ->assertJsonPath('data.units', '150.000000')
            ->assertJsonPath('data.current_value', '300000.00');

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'type' => 'withdraw',
            'amount' => 100_000,
            'nav' => 2000,
        ]);
    }

    public function test_withdraw_cannot_exceed_owned_units(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create([
            'type' => 'mutual_fund', 'units' => 10, 'current_price' => 2000,
        ]);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets/'.$asset->id.'/withdraw', ['amount' => 100_000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_user_can_only_have_one_asset(): void
    {
        $user = User::factory()->create();
        Asset::factory()->for($user)->create(['type' => 'mutual_fund']);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets', [
            'units' => 100, 'average_buy_price' => 1800,
        ])->assertUnprocessable()->assertJsonValidationErrors('asset');
    }

    public function test_movement_history_includes_nav(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create(['type' => 'mutual_fund', 'units' => 200]);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets/'.$asset->id.'/top-up', ['amount' => 100_000])->assertOk();

        $this->getJson('/api/v1/assets/'.$asset->id.'/movements')
            ->assertOk()
            ->assertJsonPath('data.0.nav', '2000.00')
            ->assertJsonPath('data.0.movement_date', '2026-11-22');
    }

    private function nextData(float $nav, string $date): string
    {
        $payload = [
            'props' => ['pageProps' => [
                'productDetail' => [
                    'name' => 'Sucorinvest Money Market Fund',
                    'nav' => ['value' => $nav, 'date' => $date],
                ],
            ]],
        ];

        return '<script id="__NEXT_DATA__" type="application/json">'.json_encode($payload).'</script>';
    }
}
