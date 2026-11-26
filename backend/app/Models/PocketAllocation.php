<?php

namespace App\Models;

use Database\Factories\PocketAllocationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PocketAllocation extends Model
{
    /** @use HasFactory<PocketAllocationFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'account_id',
        'source_pocket_id',
        'destination_pocket_id',
        'amount',
        'note',
        'allocated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'allocated_at' => 'datetime',
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

    /** @return BelongsTo<Pocket, $this> */
    public function sourcePocket(): BelongsTo
    {
        return $this->belongsTo(Pocket::class, 'source_pocket_id');
    }

    /** @return BelongsTo<Pocket, $this> */
    public function destinationPocket(): BelongsTo
    {
        return $this->belongsTo(Pocket::class, 'destination_pocket_id');
    }
}
