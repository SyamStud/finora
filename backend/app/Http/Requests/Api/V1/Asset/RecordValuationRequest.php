<?php

namespace App\Http\Requests\Api\V1\Asset;

use Illuminate\Foundation\Http\FormRequest;

class RecordValuationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'unit_price' => ['required', 'numeric', 'min:0'],
            'valuation_date' => ['required', 'date'],
        ];
    }
}
