<?php

namespace App\Http\Resources;

use App\Models\PendingTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PendingTransaction */
class PendingTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'package_name' => $this->package_name,
            'raw_text' => $this->raw_text,
            'type' => $this->type,
            'amount' => $this->amount,
            'account' => $this->whenLoaded('account', fn (): array => [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ]),
            'account_name' => $this->account_name,
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'category_name' => $this->category_name,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'confidence' => $this->confidence,
            'status' => $this->status,
            'received_at' => $this->received_at?->toISOString(),
        ];
    }
}
