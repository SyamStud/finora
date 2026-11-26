<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Asset\AssetMovementRequest;
use App\Http\Requests\Api\V1\Asset\RecordValuationRequest;
use App\Http\Requests\Api\V1\Asset\StoreAssetRequest;
use App\Http\Requests\Api\V1\Asset\UpdateAssetRequest;
use App\Http\Resources\AssetMovementResource;
use App\Http\Resources\AssetResource;
use App\Http\Resources\AssetValuationResource;
use App\Models\Asset;
use App\Services\AssetMovementService;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

final class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $assets,
        private readonly AssetMovementService $movements,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $assets = Asset::query()
            ->where('user_id', $request->user()->id)
            ->when($request->boolean('include_inactive') === false, fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get();

        return AssetResource::collection($assets);
    }

    public function store(StoreAssetRequest $request): AssetResource
    {
        return new AssetResource($this->assets->create($request->user(), $request->validated()));
    }

    public function show(Request $request, string $asset): AssetResource
    {
        return new AssetResource($this->find($request, $asset));
    }

    public function update(UpdateAssetRequest $request, string $asset): AssetResource
    {
        $model = $this->assets->update($this->find($request, $asset), $request->validated());

        return new AssetResource($model);
    }

    public function destroy(Request $request, string $asset): JsonResponse
    {
        $this->find($request, $asset)->update(['is_active' => false]);

        return response()->json(['message' => 'Aset dinonaktifkan.']);
    }

    public function valuations(Request $request, string $asset): AnonymousResourceCollection
    {
        $valuations = $this->find($request, $asset)
            ->valuations()
            ->latest('valuation_date')
            ->get();

        return AssetValuationResource::collection($valuations);
    }

    public function recordValuation(
        RecordValuationRequest $request,
        string $asset,
    ): JsonResponse {
        $model = $this->find($request, $asset);
        $valuation = $this->assets->recordValuation(
            $model,
            (float) $request->input('unit_price'),
            Carbon::parse($request->string('valuation_date')->toString()),
        )->valuations()->latest('valuation_date')->firstOrFail();

        return (new AssetValuationResource($valuation))->response()->setStatusCode(201);
    }

    /** Tambah nilai: nominal dikonversi ke unit memakai NAV terkini. */
    public function topUp(AssetMovementRequest $request, string $asset): AssetResource
    {
        $result = $this->movements->topUp(
            $this->find($request, $asset),
            (float) $request->input('amount'),
        );

        return new AssetResource($result['asset']);
    }

    /** Withdraw: nominal dikonversi ke unit memakai NAV terkini. */
    public function withdraw(AssetMovementRequest $request, string $asset): AssetResource
    {
        $result = $this->movements->withdraw(
            $this->find($request, $asset),
            (float) $request->input('amount'),
        );

        return new AssetResource($result['asset']);
    }

    /** Riwayat top up / withdraw beserta NAV yang dipakai. */
    public function movements(Request $request, string $asset): AnonymousResourceCollection
    {
        return AssetMovementResource::collection(
            $this->find($request, $asset)
                ->movements()
                ->latest('movement_date')
                ->get()
        );
    }

    private function find(Request $request, string $id): Asset
    {
        /** @var Asset $asset */
        $asset = Asset::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $asset;
    }
}
