<?php

namespace App\Http\Requests\Api\V1\Category;

use App\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where(
                    fn ($query) => $query
                        ->where('user_id', $this->user()?->id)
                        ->where('type', $this->input('type'))
                ),
            ],
            'type' => ['required', 'in:'.implode(',', TransactionType::values())],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
