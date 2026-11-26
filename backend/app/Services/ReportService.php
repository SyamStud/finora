<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReportService
{
    public function __construct(
        private readonly AccountBalanceService $balances,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(
        User $user,
        string $period = 'month',
        ?string $from = null,
        ?string $to = null,
    ): array {
        [$start, $end] = $this->range($period, $from, $to);

        $rows = DB::table('transactions')
            ->select('type', 'amount', 'category_id', 'transaction_date')
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$start, $end])
            ->get();

        $income = $rows->where('type', 'income')->sum('amount');
        $expense = $rows->where('type', 'expense')->sum('amount');

        $accountRows = Account::query()
            ->where('user_id', $user->id)
            ->get(['id', 'name', 'type', 'color', 'currency']);
        $accountBalances = $this->balances->forUser($user->id);

        $accounts = $accountRows->map(fn (Account $account): array => [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type->value,
            'color' => $account->color,
            'currency' => $account->currency,
            'balance' => $accountBalances->get($account->id, '0.00'),
        ])->all();

        $pockets = Pocket::query()
            ->where('user_id', $user->id)
            ->with('account:id,name')
            ->get()
            ->map(fn (Pocket $pocket): array => [
                'id' => $pocket->id,
                'name' => $pocket->name,
                'color' => $pocket->color,
                'balance' => $this->balances->forPocket($pocket),
                'account' => [
                    'id' => $pocket->account->id,
                    'name' => $pocket->account->name,
                ],
            ])->all();

        $cash = collect($accounts)->reduce(
            fn (string $carry, array $account): string => bcadd($carry, (string) $account['balance'], 2),
            '0.00'
        );
        $assets = (float) DB::table('assets')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('current_value');

        $netWorth = bcadd($cash, number_format($assets, 2, '.', ''), 2);

        return [
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'summary' => [
                'net_worth' => $netWorth,
                'total_cash' => $cash,
                'total_assets' => number_format($assets, 2, '.', ''),
                'income' => number_format((float) $income, 2, '.', ''),
                'expense' => number_format((float) $expense, 2, '.', ''),
                'net_cash_flow' => number_format((float) $income - (float) $expense, 2, '.', ''),
                'savings_rate' => $income > 0
                    ? round((($income - $expense) / $income) * 100, 2)
                    : 0.0,
            ],
            'charts' => [
                'cash_flow' => $this->cashFlow($rows),
                'expense_by_category' => $this->expenseByCategory($user->id, $rows),
            ],
            'accounts' => $accounts,
            'pockets' => $pockets,
            'recent_transactions' => $user->transactions()
                ->with(['account:id,name,color', 'category:id,name,color'])
                ->latest('transaction_date')
                ->limit(8)
                ->get(),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(string $period, ?string $from, ?string $to): array
    {
        if ($period === 'custom' && $from !== null && $to !== null) {
            return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        }

        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /** @param Collection<int, object> $rows */
    private function cashFlow($rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $label = Carbon::parse($row->transaction_date)->toDateString();
            $grouped[$label] ??= ['label' => $label, 'income' => 0.0, 'expense' => 0.0];
            $grouped[$label][$row->type] += (float) $row->amount;
        }
        ksort($grouped);

        return array_values($grouped);
    }

    /** @param Collection<int, object> $rows */
    private function expenseByCategory(string $userId, $rows): array
    {
        $expenses = $rows->where('type', 'expense');
        $totals = [];
        foreach ($expenses as $row) {
            $key = (string) ($row->category_id ?? 'uncategorized');
            $totals[$key] = ($totals[$key] ?? 0) + (float) $row->amount;
        }
        arsort($totals);

        $names = DB::table('categories')
            ->where('user_id', $userId)
            ->whereIn('id', array_keys($totals))
            ->pluck('name', 'id');

        return collect($totals)->map(fn (float $total, string $id): array => [
            'id' => $id,
            'name' => $names->get($id) ?? 'Uncategorized',
            'value' => number_format($total, 2, '.', ''),
        ])->values()->all();
    }
}
