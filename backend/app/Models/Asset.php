<?php

namespace App\Models;

use App\AssetType;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'provider',
        'units',
        'average_buy_price',
        'current_price',
        'invested_amount',
        'current_value',
        'currency',
        'notes',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'units' => 'decimal:6',
            'average_buy_price' => 'decimal:2',
            'current_price' => 'decimal:2',
            'invested_amount' => 'decimal:2',
            'current_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AssetValuation, $this> */
    public function valuations(): HasMany
    {
        return $this->hasMany(AssetValuation::class);
    }

    /** @return HasMany<AssetMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }
}
