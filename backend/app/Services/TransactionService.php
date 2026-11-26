<?php

namespace App\Services;

use App\InputSource;
use App\Models\Category;
use App\Models\Pocket;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Validation\ValidationException;

final class TransactionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Transaction
    {
        $this->assertCategoryMatchesType($data);
        $this->assertPocketMatchesAccount($data);

        return Transaction::query()->create(array_merge($data, [
            'user_id' => $user->id,
            'input_source' => $data['input_source'] ?? InputSource::Manual,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $merged = array_merge($transaction->toArray(), $data);
        $this->assertCategoryMatchesType($merged);
        $this->assertPocketMatchesAccount($merged);

        $transaction->update($data);

        return $transaction->refresh();
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }

    /** @param array<string, mixed> $data */
    private function assertCategoryMatchesType(array $data): void
    {
        if (empty($data['category_id'])) {
            return;
        }

        /** @var Category|null $category */
        $category = Category::query()->find($data['category_id']);
        $type = $data['type'] instanceof TransactionType
            ? $data['type']
            : TransactionType::from((string) $data['type']);

        if ($category !== null && $category->type !== $type) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori harus sesuai dengan tipe transaksi.',
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function assertPocketMatchesAccount(array $data): void
    {
        if (empty($data['pocket_id'])) {
            return;
        }

        /** @var Pocket|null $pocket */
        $pocket = Pocket::query()->find($data['pocket_id']);

        if ($pocket === null || $pocket->account_id !== $data['account_id']) {
            throw ValidationException::withMessages([
                'pocket_id' => 'Pocket harus berada di rekening yang dipilih.',
            ]);
        }
    }
}
