<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\IngestNotificationRequest;
use App\Http\Requests\Api\V1\Notification\UpdatePendingRequest;
use App\Http\Resources\PendingTransactionResource;
use App\Http\Resources\TransactionResource;
use App\Models\PendingTransaction;
use App\Services\NotificationIngestService;
use App\Services\PendingTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PendingTransactionController extends Controller
{
    public function __construct(
        private readonly NotificationIngestService $ingest,
        private readonly PendingTransactionService $pending,
    ) {}

    /** Dipakai Android listener / iOS Shortcuts / WhatsApp bot. */
    public function ingest(IngestNotificationRequest $request): JsonResponse
    {
        $record = $this->ingest->ingest($request->user(), $request->validated());

        return (new PendingTransactionResource(
            $record->load(['account:id,name', 'category:id,name'])
        ))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $records = PendingTransaction::query()
            ->where('user_id', $request->user()->id)
            ->with(['account:id,name', 'category:id,name'])
            ->latest('received_at')
            ->limit(50)
            ->get();

        return PendingTransactionResource::collection($records);
    }

    public function update(
        UpdatePendingRequest $request,
        string $pending,
    ): PendingTransactionResource {
        $model = $this->find($request, $pending);
        $model = $this->pending->update($model, $request->validated());

        return new PendingTransactionResource($model->load(['account:id,name', 'category:id,name']));
    }

    public function approve(Request $request, string $pending): TransactionResource
    {
        $transaction = $this->pending->approve($this->find($request, $pending));

        return new TransactionResource($transaction->load([
            'account:id,name,color', 'pocket:id,name,color', 'category:id,name,color',
        ]));
    }

    public function reject(Request $request, string $pending): JsonResponse
    {
        $this->pending->reject($this->find($request, $pending));

        return response()->json(['message' => 'Transaksi ditolak.']);
    }

    private function find(Request $request, string $id): PendingTransaction
    {
        /** @var PendingTransaction $record */
        $record = PendingTransaction::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $record;
    }
}
