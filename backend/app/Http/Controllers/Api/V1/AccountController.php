<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\StoreAccountRequest;
use App\Http\Requests\Api\V1\Account\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AccountController extends Controller
{
    public function __construct(private readonly AccountBalanceService $balances) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $accounts = Account::query()
            ->where('user_id', $request->user()->id)
            ->withCount('pockets')
            ->when($request->boolean('include_inactive') === false, fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get();

        $balances = $this->balances->forUser($request->user()->id);
        $accounts->each(function (Account $account) use ($balances): void {
            $account->setAttribute('balance', $balances->get($account->id, '0.00'));
        });

        return AccountResource::collection($accounts);
    }

    public function store(StoreAccountRequest $request): AccountResource
    {
        /** @var Account $account */
        $account = Account::query()->create(array_merge($request->validated(), [
            'user_id' => $request->user()->id,
        ]));
        $account->setAttribute('balance', $this->balances->forAccount($account));

        return new AccountResource($account);
    }

    public function show(Request $request, string $account): AccountResource
    {
        $model = $this->find($request, $account);
        $model->load('pockets');
        $model->setAttribute('base_balance', $this->balances->baseBalance($model));
        $model->setAttribute('pockets_balance', $this->balances->pocketsBalance($model));
        $model->setAttribute('balance', $this->balances->forAccount($model));

        return new AccountResource($model);
    }

    public function update(UpdateAccountRequest $request, string $account): AccountResource
    {
        $model = $this->find($request, $account);
        $model->update($request->validated());
        $model->setAttribute('balance', $this->balances->forAccount($model->refresh()));

        return new AccountResource($model);
    }

    /** Arsipkan akun — akun dengan riwayat tidak boleh dihapus permanen. */
    public function destroy(Request $request, string $account): JsonResponse
    {
        $model = $this->find($request, $account);
        $model->update(['is_active' => false]);

        return response()->json(['message' => 'Akun diarsipkan.']);
    }

    private function find(Request $request, string $id): Account
    {
        /** @var Account $account */
        $account = Account::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $account;
    }
}
