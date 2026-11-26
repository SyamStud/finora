<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transfer\StoreTransferRequest;
use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TransferController extends Controller
{
    public function __construct(private readonly TransferService $transfers) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $transfers = Transfer::query()
            ->where('user_id', $request->user()->id)
            ->with(['sourceAccount:id,name', 'destinationAccount:id,name'])
            ->latest('transfer_date')
            ->paginate(min(100, max(1, (int) $request->integer('per_page', 20))));

        return TransferResource::collection($transfers);
    }

    public function store(StoreTransferRequest $request): TransferResource
    {
        $transfer = $this->transfers->create($request->user(), $request->validated());

        return new TransferResource($transfer->load(['sourceAccount:id,name', 'destinationAccount:id,name']));
    }

    public function show(Request $request, string $transfer): TransferResource
    {
        return new TransferResource($this->find($request, $transfer)->load(['sourceAccount:id,name', 'destinationAccount:id,name']));
    }

    public function destroy(Request $request, string $transfer): JsonResponse
    {
        $this->transfers->delete($this->find($request, $transfer));

        return response()->json(['message' => 'Transfer dihapus.']);
    }

    private function find(Request $request, string $id): Transfer
    {
        /** @var Transfer $transfer */
        $transfer = Transfer::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $transfer;
    }
}
