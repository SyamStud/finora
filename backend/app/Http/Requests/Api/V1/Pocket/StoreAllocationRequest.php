<?php

namespace App\Http\Requests\Api\V1\Pocket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAllocationRequest extends FormRequest
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
            'account_id' => [
                'required', 'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'source_pocket_id' => [
                'nullable', 'uuid',
                Rule::exists('pockets', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'destination_pocket_id' => [
                'nullable', 'uuid',
                Rule::exists('pockets', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
            'allocated_at' => ['required', 'date'],
        ];
    }
}
