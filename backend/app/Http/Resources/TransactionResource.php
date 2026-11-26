<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'description' => $this->description,
            'notes' => $this->notes,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'input_source' => $this->input_source->value,
            'account' => $this->whenLoaded('account', fn (): array => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'color' => $this->account->color,
            ]),
            'pocket' => $this->whenLoaded('pocket', fn (): array => [
                'id' => $this->pocket->id,
                'name' => $this->pocket->name,
                'color' => $this->pocket->color,
            ]),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color,
            ]),
            'transfer_id' => $this->transfer_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
