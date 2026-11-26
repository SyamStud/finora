<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use App\Services\FundPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

final class FundController extends Controller
{
    public function __construct(
        private readonly FundPriceService $prices,
        private readonly AssetService $assets,
    ) {}

    /** Cek NAV terbaru tanpa menyimpan valuasi. */
    public function nav(): JsonResponse
    {
        try {
            return response()->json($this->prices->currentNav());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }
    }

    /** Ambil NAV terbaru lalu simpan sebagai valuasi aset. */
    public function refreshPrice(Request $request, string $asset): AssetResource|JsonResponse
    {
        /** @var Asset $model */
        $model = Asset::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($asset);

        try {
            $price = $this->prices->currentNav();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }

        $alreadyRecorded = $model->valuations()
            ->whereDate('valuation_date', $price['date'])
            ->where('source', 'api')
            ->exists();

        if ($alreadyRecorded) {
            return new AssetResource($model->refresh());
        }

        $updated = $this->assets->recordValuation(
            $model,
            $price['nav'],
            Carbon::parse($price['date']),
            'api',
        );

        return new AssetResource($updated);
    }
}
