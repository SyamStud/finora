<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AssetService
{
    public function __construct(private readonly FundPriceService $prices) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Asset
    {
        $data['user_id'] = $user->id;

        if (Asset::query()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'asset' => 'Finora hanya mendukung satu produk investasi. Gunakan Tambah nilai pada aset yang ada.',
            ]);
        }

        $data['type'] ??= 'mutual_fund';
        $data['name'] ??= (string) config('funds.rdpu.name');

        // Harga terkini reksa dana selalu diambil dari NAV Sucorinvest,
        // user hanya perlu mengisi unit dan harga beli.
        if ($data['type'] === 'mutual_fund' && empty($data['current_price'])) {
            try {
                $data['current_price'] = $this->prices->currentNav()['nav'];
            } catch (Throwable) {
                $data['current_price'] = 0;
            }
        }

        return Asset::query()->create($this->withDerivedValues($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Asset $asset, array $data): Asset
    {
        $asset->update($this->withDerivedValues(array_merge($asset->toArray(), $data)));

        return $asset->refresh();
    }

    /** Catat valuasi terbaru dan perbarui harga/nilai aset. */
    public function recordValuation(
        Asset $asset,
        float $unitPrice,
        Carbon $date,
        string $source = 'manual',
    ): Asset {
        return DB::transaction(function () use ($asset, $unitPrice, $date, $source): Asset {
            $currentValue = round((float) $asset->units * $unitPrice, 2);

            $asset->valuations()->create([
                'valuation_date' => $date->toDateString(),
                'unit_price' => $unitPrice,
                'total_value' => $currentValue,
                'source' => $source,
            ]);

            $asset->update([
                'current_price' => $unitPrice,
                'current_value' => $currentValue,
            ]);

            return $asset->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    private function withDerivedValues(array $data): array
    {
        $units = (float) ($data['units'] ?? 0);
        $currentPrice = (float) ($data['current_price'] ?? 0);
        $averageBuyPrice = (float) ($data['average_buy_price'] ?? 0);

        $data['invested_amount'] = round($units * $averageBuyPrice, 2);
        $data['current_value'] = round($units * $currentPrice, 2);

        return $data;
    }
}
