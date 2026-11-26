<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Asset */
class AssetResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $invested = (float) $this->invested_amount;
        $profit = (float) $this->current_value - $invested;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'provider' => $this->provider,
            'units' => $this->units,
            'average_buy_price' => $this->average_buy_price,
            'current_price' => $this->current_price,
            'invested_amount' => $this->invested_amount,
            'current_value' => $this->current_value,
            'profit_loss' => number_format($profit, 2, '.', ''),
            'return_percentage' => $invested > 0 ? round($profit / $invested * 100, 2) : 0.0,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ];
    }
}
