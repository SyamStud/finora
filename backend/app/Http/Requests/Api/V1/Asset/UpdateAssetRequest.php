<?php

namespace App\Http\Requests\Api\V1\Asset;

use App\AssetType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:'.implode(',', AssetType::values())],
            'provider' => ['nullable', 'string', 'max:255'],
            'units' => ['sometimes', 'numeric', 'min:0'],
            'average_buy_price' => ['sometimes', 'numeric', 'min:0'],
            'current_price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
