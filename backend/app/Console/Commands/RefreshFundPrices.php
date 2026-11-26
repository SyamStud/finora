<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\AssetService;
use App\Services\FundPriceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'fund:refresh-prices',
    description: 'Ambil NAV terbaru dan perbarui valuasi aset reksa dana',
)]
class RefreshFundPrices extends Command
{
    /** Execute the console command. */
    public function handle(FundPriceService $prices, AssetService $assets): int
    {
        try {
            $price = $prices->currentNav();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $date = Carbon::parse($price['date']);
        $updated = 0;

        Asset::query()
            ->where('is_active', true)
            ->where('type', 'mutual_fund')
            ->each(function (Asset $asset) use ($assets, $price, $date, &$updated): void {
                $exists = $asset->valuations()
                    ->whereDate('valuation_date', $price['date'])
                    ->where('source', 'api')
                    ->exists();

                if ($exists) {
                    return;
                }

                $assets->recordValuation($asset, $price['nav'], $date, 'api');
                $updated++;
            });

        $this->info(sprintf(
            'NAV %s per %s: %s. %d aset diperbarui.',
            $price['name'],
            $price['date'],
            number_format($price['nav'], 2),
            $updated
        ));

        return self::SUCCESS;
    }
}
