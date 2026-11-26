<?php

namespace App\Http\Requests\Api\V1\Category;

use App\Models\Category;
use App\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $category = Category::query()->find($this->route('category'));
        $id = $category?->id;
        $type = $this->input('type', $category?->type?->value ?? 'expense');

        return [
            'name' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('categories', 'name')->ignore($id)->where(
                    fn ($query) => $query
                        ->where('user_id', $this->user()?->id)
                        ->where('type', $type)
                ),
            ],
            'type' => ['sometimes', 'in:'.implode(',', TransactionType::values())],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
