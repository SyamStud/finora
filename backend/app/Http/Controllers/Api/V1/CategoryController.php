<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Category\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->boolean('include_inactive') === false, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        /** @var Category $category */
        $category = Category::query()->create(array_merge($request->validated(), [
            'user_id' => $request->user()->id,
        ]));

        return new CategoryResource($category);
    }

    public function show(Request $request, string $category): CategoryResource
    {
        return new CategoryResource($this->find($request, $category));
    }

    public function update(UpdateCategoryRequest $request, string $category): CategoryResource
    {
        $model = $this->find($request, $category);
        $model->update($request->validated());

        return new CategoryResource($model->refresh());
    }

    public function destroy(Request $request, string $category): JsonResponse
    {
        $this->find($request, $category)->update(['is_active' => false]);

        return response()->json(['message' => 'Kategori diarsipkan.']);
    }

    private function find(Request $request, string $id): Category
    {
        /** @var Category $category */
        $category = Category::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $category;
    }
}
