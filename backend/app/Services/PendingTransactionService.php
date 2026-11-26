<?php

namespace App\Services;

use App\InputSource;
use App\Models\PendingTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PendingTransactionService
{
    public function __construct(private readonly TransactionService $transactions) {}

    /** @param array<string, mixed> $data */
    public function update(PendingTransaction $pending, array $data): PendingTransaction
    {
        $this->assertPending($pending);
        $pending->update(array_filter($data, fn ($value) => $value !== null));

        return $pending->refresh();
    }

    public function approve(PendingTransaction $pending): Transaction
    {
        $this->assertPending($pending);

        if ($pending->account_id === null || $pending->amount === null || (float) $pending->amount <= 0) {
            throw ValidationException::withMessages([
                'account_id' => 'Lengkapi rekening dan nominal sebelum menyetujui.',
            ]);
        }

        return DB::transaction(function () use ($pending): Transaction {
            /** @var Transaction $transaction */
            $transaction = $this->transactions->create($pending->user, [
                'type' => $pending->type ?? 'expense',
                'amount' => $pending->amount,
                'account_id' => $pending->account_id,
                'category_id' => $pending->category_id,
                'description' => $pending->description ?: 'Transaksi dari notifikasi',
                'transaction_date' => $pending->transaction_date ?? now(),
                'input_source' => InputSource::Import,
            ]);

            $pending->update([
                'status' => 'approved',
                'approved_transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function reject(PendingTransaction $pending): void
    {
        $this->assertPending($pending);
        $pending->update(['status' => 'rejected']);
    }

    private function assertPending(PendingTransaction $pending): void
    {
        if ($pending->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Transaksi ini sudah diproses.',
            ]);
        }
    }
}
