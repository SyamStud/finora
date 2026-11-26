<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AssetMovementService
{
    public function __construct(private readonly FundPriceService $prices) {}

    /** @return array{asset: Asset, movement: AssetMovement, nav: float, nav_date: string} */
    public function topUp(Asset $asset, float $amount): array
    {
        $price = $this->nav();
        $nav = $price['nav'];
        $unitsAdded = round($amount / $nav, 6);

        $oldUnits = (float) $asset->units;
        $oldInvested = (float) $asset->invested_amount;
        $newUnits = round($oldUnits + $unitsAdded, 6);
        $newInvested = round($oldInvested + $amount, 2);
        $averageBuyPrice = $newUnits > 0 ? round($newInvested / $newUnits, 2) : 0;

        return DB::transaction(function () use (
            $asset, $amount, $unitsAdded, $newUnits, $newInvested,
            $averageBuyPrice, $nav, $price
        ): array {
            $asset->update([
                'units' => $newUnits,
                'average_buy_price' => $averageBuyPrice,
                'invested_amount' => $newInvested,
                'current_price' => $nav,
                'current_value' => round($newUnits * $nav, 2),
            ]);

            $movement = $asset->movements()->create([
                'user_id' => $asset->user_id,
                'type' => 'topup',
                'amount' => $amount,
                'units' => $unitsAdded,
                'nav' => $nav,
                'movement_date' => $price['date'],
                'note' => 'NAV per '.$price['date'],
            ]);

            return ['asset' => $asset->refresh(), 'movement' => $movement, 'nav' => $nav, 'nav_date' => $price['date']];
        });
    }

    /** @return array{asset: Asset, movement: AssetMovement, nav: float, nav_date: string} */
    public function withdraw(Asset $asset, float $amount): array
    {
        $price = $this->nav();
        $nav = $price['nav'];
        $unitsRemoved = round($amount / $nav, 6);
        $currentUnits = (float) $asset->units;

        if ($unitsRemoved > $currentUnits) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal penarikan melebihi jumlah unit yang dimiliki.',
            ]);
        }

        $newUnits = round($currentUnits - $unitsRemoved, 6);
        $averageBuyPrice = (float) $asset->average_buy_price;
        $investedReduction = round($unitsRemoved * $averageBuyPrice, 2);
        $newInvested = max(0, round((float) $asset->invested_amount - $investedReduction, 2));

        return DB::transaction(function () use (
            $asset, $amount, $unitsRemoved, $newUnits, $newInvested,
            $averageBuyPrice, $nav, $price
        ): array {
            $asset->update([
                'units' => $newUnits,
                'average_buy_price' => $averageBuyPrice,
                'invested_amount' => $newInvested,
                'current_price' => $nav,
                'current_value' => round($newUnits * $nav, 2),
            ]);

            $movement = $asset->movements()->create([
                'user_id' => $asset->user_id,
                'type' => 'withdraw',
                'amount' => $amount,
                'units' => $unitsRemoved,
                'nav' => $nav,
                'movement_date' => $price['date'],
                'note' => 'NAV per '.$price['date'],
            ]);

            return ['asset' => $asset->refresh(), 'movement' => $movement, 'nav' => $nav, 'nav_date' => $price['date']];
        });
    }

    /** @return array{name: string, code: string, nav: float, date: string, source: string, cached: bool} */
    private function nav(): array
    {
        try {
            return $this->prices->currentNav();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'amount' => 'NAV belum tersedia. Coba lagi sebentar.',
            ]);
        }
    }
}
