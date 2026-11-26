<?php

namespace App\Http\Resources;

use App\Models\AssetValuation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssetValuation */
class AssetValuationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'valuation_date' => $this->valuation_date?->toDateString(),
            'unit_price' => $this->unit_price,
            'total_value' => $this->total_value,
            'source' => $this->source,
        ];
    }
}
