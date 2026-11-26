<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Pocket;
use App\TransactionType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Saldo selalu dihitung turunan dari pergerakan dana, bukan disimpan sebagai
 * kolom. Ini membuat saldo tetap benar setelah transaksi diedit atau dihapus.
 *
 * Saldo Dasar   = saldo awal + transaksi tanpa pocket + transfer + alokasi
 * Saldo Pocket  = alokasi masuk - alokasi keluar + transaksi yang dibebankan ke pocket
 * Saldo Rekening = Saldo Dasar + semua Saldo Pocket di rekening itu
 *
 * Alokasi tidak mengubah Saldo Rekening: uang hanya berpindah antara saldo
 * dasar dan pocket (atau antar pocket) di dalam rekening yang sama.
 */
final class AccountBalanceService
{
    /** Total saldo satu rekening, termasuk seluruh pocket di dalamnya. */
    public function forAccount(Account $account): string
    {
        return bcadd($this->baseBalance($account), $this->pocketsBalance($account), 2);
    }

    /** Saldo dasar rekening: bagian yang belum dialokasikan ke pocket. */
    public function baseBalance(Account $account): string
    {
        $balance = $this->money($account->initial_balance);

        $transactions = DB::table('transactions')
            ->select('type', DB::raw('SUM(amount) as total'))
            ->where('account_id', $account->id)
            ->whereNull('pocket_id')
            ->groupBy('type')
            ->pluck('total', 'type');

        $balance = bcadd($balance, $this->money($transactions->get(TransactionType::Income->value)), 2);
        $balance = bcsub($balance, $this->money($transactions->get(TransactionType::Expense->value)), 2);

        $transferIn = DB::table('transfers')
            ->where('destination_account_id', $account->id)
            ->sum('amount');
        $transferOut = DB::table('transfers')
            ->where('source_account_id', $account->id)
            ->sum('amount');

        $balance = bcadd($balance, $this->money($transferIn), 2);
        $balance = bcsub($balance, $this->money($transferOut), 2);

        $toBase = DB::table('pocket_allocations')
            ->where('account_id', $account->id)
            ->whereNull('destination_pocket_id')
            ->sum('amount');
        $fromBase = DB::table('pocket_allocations')
            ->where('account_id', $account->id)
            ->whereNull('source_pocket_id')
            ->sum('amount');

        $balance = bcadd($balance, $this->money($toBase), 2);

        return bcsub($balance, $this->money($fromBase), 2);
    }

    /** Saldo satu pocket, keyed by pocket id. */
    public function forPocket(Pocket $pocket): string
    {
        $in = DB::table('pocket_allocations')
            ->where('destination_pocket_id', $pocket->id)
            ->sum('amount');
        $out = DB::table('pocket_allocations')
            ->where('source_pocket_id', $pocket->id)
            ->sum('amount');

        $transactions = DB::table('transactions')
            ->select('type', DB::raw('SUM(amount) as total'))
            ->where('pocket_id', $pocket->id)
            ->groupBy('type')
            ->pluck('total', 'type');

        $balance = $this->money($in);
        $balance = bcsub($balance, $this->money($out), 2);
        $balance = bcadd($balance, $this->money($transactions->get(TransactionType::Income->value)), 2);

        return bcsub($balance, $this->money($transactions->get(TransactionType::Expense->value)), 2);
    }

    /** @return Collection<string, string> Saldo tiap pocket, keyed by pocket id. */
    public function pocketBalances(Account $account): Collection
    {
        return $account->pockets()
            ->get(['id'])
            ->mapWithKeys(fn (Pocket $pocket): array => [$pocket->id => $this->forPocket($pocket)]);
    }

    /** Jumlah seluruh saldo pocket di satu rekening. */
    public function pocketsBalance(Account $account): string
    {
        return $this->pocketBalances($account)->reduce(
            fn (string $carry, string $balance): string => bcadd($carry, $balance, 2),
            '0.00'
        );
    }

    /**
     * Total saldo semua rekening milik user, keyed by account id.
     * Sudah termasuk pocket, jadi bisa langsung dijumlahkan untuk total kas.
     *
     * @return Collection<string, string>
     */
    public function forUser(string $userId): Collection
    {
        $accounts = Account::query()
            ->where('user_id', $userId)
            ->get(['id', 'user_id', 'initial_balance']);

        $pockets = Pocket::query()
            ->where('user_id', $userId)
            ->get(['id', 'account_id']);

        /** @var Collection<string, string> $base */
        $base = $accounts->mapWithKeys(
            fn (Account $account): array => [$account->id => $this->money($account->initial_balance)]
        );
        /** @var Collection<string, string> $pocketBalances */
        $pocketBalances = $pockets->mapWithKeys(
            fn (Pocket $pocket): array => [$pocket->id => '0.00']
        );

        $transactionRows = DB::table('transactions')
            ->select('account_id', 'pocket_id', 'type', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->groupBy('account_id', 'pocket_id', 'type')
            ->get();

        foreach ($transactionRows as $row) {
            $delta = $this->money($row->total);
            $sign = $row->type === TransactionType::Income->value ? 1 : -1;

            if ($row->pocket_id !== null && $pocketBalances->has($row->pocket_id)) {
                $key = (string) $row->pocket_id;
                $current = $pocketBalances->get($key, '0.00');
                $pocketBalances->put($key, $sign > 0 ? bcadd($current, $delta, 2) : bcsub($current, $delta, 2));
            } elseif ($base->has($row->account_id)) {
                $key = (string) $row->account_id;
                $current = $base->get($key, '0.00');
                $base->put($key, $sign > 0 ? bcadd($current, $delta, 2) : bcsub($current, $delta, 2));
            }
        }

        $transferIn = DB::table('transfers')
            ->select('destination_account_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->groupBy('destination_account_id')
            ->pluck('total', 'destination_account_id');
        foreach ($transferIn as $accountId => $total) {
            if ($base->has($accountId)) {
                $base->put($accountId, bcadd($base->get($accountId, '0.00'), $this->money($total), 2));
            }
        }

        $transferOut = DB::table('transfers')
            ->select('source_account_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->groupBy('source_account_id')
            ->pluck('total', 'source_account_id');
        foreach ($transferOut as $accountId => $total) {
            if ($base->has($accountId)) {
                $base->put($accountId, bcsub($base->get($accountId, '0.00'), $this->money($total), 2));
            }
        }

        $allocations = DB::table('pocket_allocations')
            ->select('account_id', 'source_pocket_id', 'destination_pocket_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->groupBy('account_id', 'source_pocket_id', 'destination_pocket_id')
            ->get();

        foreach ($allocations as $row) {
            $total = $this->money($row->total);

            if ($row->destination_pocket_id === null) {
                if ($base->has($row->account_id)) {
                    $base->put($row->account_id, bcadd($base->get($row->account_id, '0.00'), $total, 2));
                }
            } elseif ($pocketBalances->has($row->destination_pocket_id)) {
                $key = (string) $row->destination_pocket_id;
                $pocketBalances->put($key, bcadd($pocketBalances->get($key, '0.00'), $total, 2));
            }

            if ($row->source_pocket_id === null) {
                if ($base->has($row->account_id)) {
                    $base->put($row->account_id, bcsub($base->get($row->account_id, '0.00'), $total, 2));
                }
            } elseif ($pocketBalances->has($row->source_pocket_id)) {
                $key = (string) $row->source_pocket_id;
                $pocketBalances->put($key, bcsub($pocketBalances->get($key, '0.00'), $total, 2));
            }
        }

        return $accounts->mapWithKeys(function (Account $account) use ($base, $pockets, $pocketBalances): array {
            $total = $base->get($account->id, '0.00');
            $pocketTotal = $pockets
                ->where('account_id', $account->id)
                ->reduce(fn (string $carry, Pocket $pocket) => bcadd($carry, $pocketBalances->get($pocket->id, '0.00'), 2), '0.00');

            return [$account->id => bcadd($total, $pocketTotal, 2)];
        });
    }

    private function money(float|string|int|null $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
