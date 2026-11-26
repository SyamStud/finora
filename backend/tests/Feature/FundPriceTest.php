<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Services\FundPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FundPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_nav_is_read_from_bibit_next_data(): void
    {
        $this->fakeBibitResponse(1988.42, '2026-11-19');

        $nav = app(FundPriceService::class)->currentNav();

        $this->assertSame(1988.42, $nav['nav']);
        $this->assertSame('2026-11-19', $nav['date']);
        $this->assertSame('Sucorinvest Money Market Fund', $nav['name']);
        $this->assertFalse($nav['cached']);
    }

    public function test_nav_is_cached(): void
    {
        $this->fakeBibitResponse(1988.42, '2026-11-19');

        app(FundPriceService::class)->currentNav();
        $second = app(FundPriceService::class)->currentNav();

        $this->assertTrue($second['cached']);
        Http::assertSentCount(1);
    }

    public function test_endpoint_can_check_nav_without_saving(): void
    {
        $this->fakeBibitResponse(2005.19, '2026-11-20');
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/funds/nav')
            ->assertOk()
            ->assertJsonPath('nav', 2005.19)
            ->assertJsonPath('date', '2026-11-20');

        $this->assertDatabaseCount('asset_valuations', 0);
    }

    public function test_asset_price_can_be_refreshed_from_nav(): void
    {
        $this->fakeBibitResponse(2000.0, '2026-11-20');
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create([
            'units' => 1000,
            'current_price' => 1000,
            'current_value' => 1_000_000,
        ]);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets/'.$asset->id.'/refresh-price')
            ->assertOk()
            ->assertJsonPath('data.current_price', '2000.00')
            ->assertJsonPath('data.current_value', '2000000.00');

        $this->assertDatabaseHas('asset_valuations', [
            'asset_id' => $asset->id,
            'unit_price' => 2000,
            'source' => 'api',
        ]);
    }

    public function test_refresh_is_idempotent_for_the_same_day(): void
    {
        $this->fakeBibitResponse(2000.0, '2026-11-20');
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create(['units' => 1000]);
        $this->actingAs($user);

        $this->postJson('/api/v1/assets/'.$asset->id.'/refresh-price')->assertOk();
        $this->postJson('/api/v1/assets/'.$asset->id.'/refresh-price')->assertOk();

        $this->assertDatabaseCount('asset_valuations', 1);
    }

    private function fakeBibitResponse(float $nav, string $date): void
    {
        $payload = [
            'props' => ['pageProps' => [
                'productDetail' => [
                    'name' => 'Sucorinvest Money Market Fund',
                    'nav' => ['value' => $nav, 'date' => $date],
                ],
            ]],
        ];

        Http::fake([
            'bibit.id/*' => Http::response(
                '<script id="__NEXT_DATA__" type="application/json">'.json_encode($payload).'</script>'
            ),
        ]);
    }
}
