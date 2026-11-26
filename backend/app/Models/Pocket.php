<?php

namespace App\Models;

use Database\Factories\PocketFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pocket extends Model
{
    /** @use HasFactory<PocketFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'icon',
        'color',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<PocketAllocation, $this> */
    public function incomingAllocations(): HasMany
    {
        return $this->hasMany(PocketAllocation::class, 'destination_pocket_id');
    }

    /** @return HasMany<PocketAllocation, $this> */
    public function outgoingAllocations(): HasMany
    {
        return $this->hasMany(PocketAllocation::class, 'source_pocket_id');
    }
}
