<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Account */
class AccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'currency' => $this->currency,
            'initial_balance' => $this->initial_balance,
            'balance' => $this->when(isset($this->balance), $this->balance),
            'base_balance' => $this->when(isset($this->base_balance), $this->base_balance),
            'pockets_balance' => $this->when(isset($this->pockets_balance), $this->pockets_balance),
            'pockets' => $this->whenLoaded('pockets'),
            'pocket_count' => $this->when(isset($this->pockets_count), $this->pockets_count),
            'icon' => $this->icon,
            'color' => $this->color,
            'is_active' => $this->is_active,
        ];
    }
}
