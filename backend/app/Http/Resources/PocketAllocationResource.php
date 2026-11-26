<?php

namespace App\Http\Resources;

use App\Models\PocketAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PocketAllocation */
class PocketAllocationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->sourcePocket ? $this->sourcePocket->name : 'Saldo dasar',
            'destination' => $this->destinationPocket ? $this->destinationPocket->name : 'Saldo dasar',
            'amount' => $this->amount,
            'note' => $this->note,
            'allocated_at' => $this->allocated_at?->toISOString(),
        ];
    }
}
