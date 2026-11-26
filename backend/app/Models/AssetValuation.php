<?php

namespace App\Models;

use Database\Factories\AssetValuationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetValuation extends Model
{
    /** @use HasFactory<AssetValuationFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'asset_id',
        'valuation_date',
        'unit_price',
        'total_value',
        'source',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valuation_date' => 'date',
            'unit_price' => 'decimal:2',
            'total_value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
