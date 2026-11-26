<?php

namespace App\Http\Requests\Api\V1\Transaction;

use App\InputSource;
use App\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'type' => ['required', 'in:'.implode(',', TransactionType::values())],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => [
                'required', 'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'pocket_id' => [
                'nullable', 'uuid',
                Rule::exists('pockets', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'category_id' => [
                'nullable', 'uuid',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
            'input_source' => ['sometimes', 'in:'.implode(',', InputSource::values())],
        ];
    }
}
