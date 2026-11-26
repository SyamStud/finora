<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transaction\StoreTransactionRequest;
use App\Http\Requests\Api\V1\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with(['account:id,name,color', 'pocket:id,name,color', 'category:id,name,color'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')->toString()))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->string('account_id')->toString()))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->string('category_id')->toString()))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->string('from')->toString()))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->string('to')->toString()))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($inner) => $inner
                    ->whereLike('description', $term)
                    ->orWhereLike('notes', $term));
            });

        $sort = $request->string('sort')->toString();
        $query->orderBy(
            in_array($sort, ['amount', 'transaction_date'], true) ? $sort : 'transaction_date',
            $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc'
        );

        $transactions = $query->paginate(min(100, max(1, (int) $request->integer('per_page', 20))));

        return TransactionResource::collection($transactions);
    }

    public function store(StoreTransactionRequest $request): TransactionResource
    {
        $transaction = $this->transactions->create($request->user(), $request->validated());

        return new TransactionResource($transaction->load(['account:id,name,color', 'pocket:id,name,color', 'category:id,name,color']));
    }

    public function show(Request $request, string $transaction): TransactionResource
    {
        return new TransactionResource($this->find($request, $transaction)->load(['account:id,name,color', 'pocket:id,name,color', 'category:id,name,color']));
    }

    public function update(UpdateTransactionRequest $request, string $transaction): TransactionResource
    {
        $model = $this->find($request, $transaction);
        $this->transactions->update($model, $request->validated());

        return new TransactionResource($model->load(['account:id,name,color', 'pocket:id,name,color', 'category:id,name,color']));
    }

    public function destroy(Request $request, string $transaction): JsonResponse
    {
        $this->transactions->delete($this->find($request, $transaction));

        return response()->json(['message' => 'Transaksi dihapus.']);
    }

    private function find(Request $request, string $id): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $transaction;
    }
}
