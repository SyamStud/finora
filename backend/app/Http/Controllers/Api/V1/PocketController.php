<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Pocket\StoreAllocationRequest;
use App\Http\Requests\Api\V1\Pocket\StorePocketRequest;
use App\Http\Requests\Api\V1\Pocket\UpdatePocketRequest;
use App\Http\Resources\PocketAllocationResource;
use App\Http\Resources\PocketResource;
use App\Models\Account;
use App\Models\Pocket;
use App\Services\AccountBalanceService;
use App\Services\PocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PocketController extends Controller
{
    public function __construct(
        private readonly PocketService $pockets,
        private readonly AccountBalanceService $balances,
    ) {}

    /** Semua pocket milik user, lintas rekening. */
    public function all(Request $request): AnonymousResourceCollection
    {
        $balances = $this->balances->forUser($request->user()->id);

        $pockets = Pocket::query()
            ->where('user_id', $request->user()->id)
            ->with('account:id,name,color')
            ->get()
            ->each(function (Pocket $pocket) use ($balances): void {
                $pocket->setAttribute('balance', $this->balances->forPocket($pocket));
                $pocket->setAttribute('account_balance', $balances->get($pocket->account_id, '0.00'));
            });

        return PocketResource::collection($pockets);
    }

    /** @return AnonymousResourceCollection<PocketResource> */
    public function index(Request $request, string $account): AnonymousResourceCollection
    {
        $model = $this->account($request, $account);
        $balances = $this->balances->pocketBalances($model);

        $pockets = $model->pockets()
            ->when($request->boolean('include_inactive') === false, fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get()
            ->each(function (Pocket $pocket) use ($balances): void {
                $pocket->setAttribute('balance', $balances->get($pocket->id, '0.00'));
            });

        return PocketResource::collection($pockets);
    }

    public function store(StorePocketRequest $request, string $account): JsonResponse
    {
        $model = $this->account($request, $account);
        $pocket = $this->pockets->create($request->user(), $model, $request->validated());
        $pocket->setAttribute('balance', '0.00');

        return (new PocketResource($pocket))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $pocket): PocketResource
    {
        $model = $this->pocket($request, $pocket);
        $model->setAttribute('balance', $this->balances->forPocket($model));

        return new PocketResource($model);
    }

    public function update(UpdatePocketRequest $request, string $pocket): PocketResource
    {
        $model = $this->pocket($request, $pocket);
        $model = $this->pockets->update($model, $request->validated());
        $model->setAttribute('balance', $this->balances->forPocket($model));

        return new PocketResource($model);
    }

    public function destroy(Request $request, string $pocket): JsonResponse
    {
        $this->pockets->archive($this->pocket($request, $pocket));

        return response()->json(['message' => 'Pocket diarsipkan.']);
    }

    /** Riwayat alokasi dalam satu rekening. */
    public function allocations(Request $request, string $account): AnonymousResourceCollection
    {
        $model = $this->account($request, $account);

        return PocketAllocationResource::collection(
            $model->allocations()->with(['sourcePocket:id,name', 'destinationPocket:id,name'])
                ->latest('allocated_at')->get()
        );
    }

    public function allocate(StoreAllocationRequest $request): JsonResponse
    {
        $allocation = $this->pockets->allocate($request->user(), $request->validated());

        return (new PocketAllocationResource(
            $allocation->load(['sourcePocket:id,name', 'destinationPocket:id,name'])
        ))->response()->setStatusCode(201);
    }

    private function account(Request $request, string $id): Account
    {
        /** @var Account $account */
        $account = Account::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $account;
    }

    private function pocket(Request $request, string $id): Pocket
    {
        /** @var Pocket $pocket */
        $pocket = Pocket::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $pocket;
    }
}
