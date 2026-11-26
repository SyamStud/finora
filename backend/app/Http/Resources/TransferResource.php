<?php

namespace App\Http\Resources;

use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transfer */
class TransferResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'description' => $this->description,
            'transfer_date' => $this->transfer_date?->toISOString(),
            'source_account' => $this->whenLoaded('sourceAccount', fn (): array => [
                'id' => $this->sourceAccount->id,
                'name' => $this->sourceAccount->name,
            ]),
            'destination_account' => $this->whenLoaded('destinationAccount', fn (): array => [
                'id' => $this->destinationAccount->id,
                'name' => $this->destinationAccount->name,
            ]),
        ];
    }
}
