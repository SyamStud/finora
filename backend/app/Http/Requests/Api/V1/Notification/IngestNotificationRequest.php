<?php

namespace App\Http\Requests\Api\V1\Notification;

use Illuminate\Foundation\Http\FormRequest;

class IngestNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'raw_text' => ['required', 'string', 'max:5000'],
            'source' => ['sometimes', 'in:notification,whatsapp,share'],
            'package_name' => ['nullable', 'string', 'max:150'],
            'received_at' => ['nullable', 'date'],
        ];
    }
}
