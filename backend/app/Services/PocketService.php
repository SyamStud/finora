<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Pocket;
use App\Models\PocketAllocation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PocketService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, Account $account, array $data): Pocket
    {
        $this->assertAccountOwnedBy($user, $account);

        return Pocket::query()->create(array_merge($data, [
            'user_id' => $user->id,
            'account_id' => $account->id,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Pocket $pocket, array $data): Pocket
    {
        $pocket->update($data);

        return $pocket->refresh();
    }

    public function archive(Pocket $pocket): void
    {
        $pocket->update(['is_active' => false]);
    }

    /**
     * Alokasi internal: memindahkan uang antara saldo dasar dan pocket, atau
     * antar pocket di dalam satu rekening. Tidak memengaruhi total rekening.
     *
     * @param  array<string, mixed>  $data
     */
    public function allocate(User $user, array $data): PocketAllocation
    {
        /** @var Account $account */
        $account = Account::query()
            ->where('user_id', $user->id)
            ->findOrFail($data['account_id']);

        $sourceId = $data['source_pocket_id'] ?? null;
        $destinationId = $data['destination_pocket_id'] ?? null;

        if ($sourceId === null && $destinationId === null) {
            throw ValidationException::withMessages([
                'destination_pocket_id' => 'Pilih pocket tujuan atau saldo dasar.',
            ]);
        }

        if ($sourceId !== null && $sourceId === $destinationId) {
            throw ValidationException::withMessages([
                'destination_pocket_id' => 'Pocket sumber dan tujuan harus berbeda.',
            ]);
        }

        foreach ([$sourceId, $destinationId] as $pocketId) {
            if ($pocketId !== null) {
                $this->assertPocketInAccount($pocketId, $account);
            }
        }

        return DB::transaction(fn (): PocketAllocation => PocketAllocation::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'source_pocket_id' => $sourceId,
            'destination_pocket_id' => $destinationId,
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'allocated_at' => Carbon::parse($data['allocated_at']),
        ]));
    }

    private function assertAccountOwnedBy(User $user, Account $account): void
    {
        if ($account->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'account_id' => 'Rekening bukan milik Anda.',
            ]);
        }
    }

    private function assertPocketInAccount(string $pocketId, Account $account): void
    {
        /** @var Pocket|null $pocket */
        $pocket = Pocket::query()->find($pocketId);

        if ($pocket === null || $pocket->account_id !== $account->id) {
            throw ValidationException::withMessages([
                'pocket_id' => 'Pocket tidak ditemukan di rekening ini.',
            ]);
        }
    }
}
