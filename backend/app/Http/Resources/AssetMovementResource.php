<?php

namespace App\Http\Resources;

use App\Models\AssetMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssetMovement */
class AssetMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'units' => $this->units,
            'nav' => $this->nav,
            'movement_date' => $this->movement_date?->toDateString(),
            'note' => $this->note,
        ];
    }
}
