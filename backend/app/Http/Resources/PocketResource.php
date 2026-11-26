<?php

namespace App\Http\Resources;

use App\Models\Pocket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pocket */
class PocketResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_active' => $this->is_active,
            'balance' => $this->when(isset($this->balance), $this->balance),
            'account' => $this->whenLoaded('account', fn (): array => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'color' => $this->account->color,
            ]),
            'account_balance' => $this->when(isset($this->account_balance), $this->account_balance),
        ];
    }
}
