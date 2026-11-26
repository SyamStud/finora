<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Budget\StoreBudgetRequest;
use App\Http\Requests\Api\V1\Budget\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgets) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $budgets = Budget::query()
            ->where('user_id', $request->user()->id)
            ->with('category:id,name,color')
            ->latest()
            ->get()
            ->each(function (Budget $budget): void {
                $budget->setAttribute('usage', $this->budgets->usage($budget));
            });

        return BudgetResource::collection($budgets);
    }

    public function store(StoreBudgetRequest $request): BudgetResource
    {
        /** @var Budget $budget */
        $budget = Budget::query()->create(array_merge($request->validated(), [
            'user_id' => $request->user()->id,
        ]));
        $budget->setAttribute('usage', $this->budgets->usage($budget));

        return new BudgetResource($budget->load('category:id,name,color'));
    }

    public function show(Request $request, string $budget): BudgetResource
    {
        $model = $this->find($request, $budget);
        $model->setAttribute('usage', $this->budgets->usage($model));

        return new BudgetResource($model->load('category:id,name,color'));
    }

    public function update(UpdateBudgetRequest $request, string $budget): BudgetResource
    {
        $model = $this->find($request, $budget);
        $model->update($request->validated());
        $model->setAttribute('usage', $this->budgets->usage($model->refresh()));

        return new BudgetResource($model->load('category:id,name,color'));
    }

    public function destroy(Request $request, string $budget): JsonResponse
    {
        $this->find($request, $budget)->delete();

        return response()->json(['message' => 'Budget dihapus.']);
    }

    private function find(Request $request, string $id): Budget
    {
        /** @var Budget $budget */
        $budget = Budget::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $budget;
    }
}
