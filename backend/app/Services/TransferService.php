<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transfer;
use App\Models\User;
use App\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransferService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Transfer
    {
        $sourceId = (string) $data['source_account_id'];
        $destinationId = (string) $data['destination_account_id'];

        if ($sourceId === $destinationId) {
            throw ValidationException::withMessages([
                'destination_account_id' => 'Akun tujuan harus berbeda dengan akun sumber.',
            ]);
        }

        $this->assertOwnedBy($user, $sourceId, 'source_account_id');
        $this->assertOwnedBy($user, $destinationId, 'destination_account_id');

        return DB::transaction(function () use ($user, $data): Transfer {
            /** @var Transfer $transfer */
            $transfer = Transfer::query()->create(array_merge($data, ['user_id' => $user->id]));

            $fee = (float) ($data['fee'] ?? 0);
            if ($fee > 0) {
                $this->recordFee($user, $transfer, $fee);
            }

            return $transfer;
        });
    }

    public function delete(Transfer $transfer): void
    {
        DB::transaction(fn (): bool => $transfer->delete());
    }

    private function assertOwnedBy(User $user, string $accountId, string $field): void
    {
        $exists = Account::query()
            ->where('id', $accountId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                $field => 'Akun tidak ditemukan atau bukan milik Anda.',
            ]);
        }
    }

    private function recordFee(User $user, Transfer $transfer, float $fee): void
    {
        /** @var Category $category */
        $category = Category::query()->firstOrCreate([
            'user_id' => $user->id,
            'name' => 'Other',
            'type' => TransactionType::Expense,
        ], [
            'icon' => 'receipt',
            'color' => '#6b7280',
        ]);

        $transfer->feeTransaction()->create([
            'user_id' => $user->id,
            'account_id' => $transfer->source_account_id,
            'category_id' => $category->id,
            'type' => TransactionType::Expense,
            'amount' => $fee,
            'description' => 'Biaya transfer',
            'notes' => 'Fee untuk transfer '.$transfer->id,
            'transaction_date' => $transfer->transfer_date,
            'input_source' => 'manual',
        ]);
    }
}
