<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\PendingTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PendingTransaction>
 */
class PendingTransactionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source' => 'notification',
            'package_name' => 'com.bca',
            'raw_text' => 'Beli kopi 25rb pakai BCA',
            'type' => 'expense',
            'amount' => 25_000,
            'account_id' => Account::factory(),
            'account_name' => 'BCA',
            'description' => 'Beli kopi',
            'transaction_date' => now(),
            'confidence' => 0.85,
            'status' => 'pending',
            'fingerprint' => Str::uuid()->toString(),
            'received_at' => now(),
        ];
    }
}
