<?php

namespace App\Http\Requests\Api\V1\Budget;

use App\BudgetPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes', 'uuid',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query
                        ->where('user_id', $this->user()?->id)
                        ->where('type', 'expense')
                ),
            ],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'period' => ['sometimes', 'in:'.implode(',', BudgetPeriod::values())],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }
}
