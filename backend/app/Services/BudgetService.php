<?php

namespace App\Services;

use App\Models\Budget;
use Illuminate\Support\Facades\DB;

final class BudgetService
{
    /**
     * @return array{amount: string, used: string, remaining: string, percentage: float}
     */
    public function usage(Budget $budget): array
    {
        [$from, $to] = $this->activeRange($budget);

        $used = (float) DB::table('transactions')
            ->where('user_id', $budget->user_id)
            ->where('category_id', $budget->category_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        $amount = (float) $budget->amount;
        $percentage = $amount > 0 ? round($used / $amount * 100, 2) : 0.0;

        return [
            'amount' => number_format($amount, 2, '.', ''),
            'used' => number_format($used, 2, '.', ''),
            'remaining' => number_format(max(0, $amount - $used), 2, '.', ''),
            'percentage' => $percentage,
        ];
    }

    /** @return array{0: string, 1: string} */
    private function activeRange(Budget $budget): array
    {
        if ($budget->end_date !== null) {
            return [$budget->start_date->toDateString(), $budget->end_date->toDateString()];
        }

        $anchor = now();
        if ($budget->period->value === 'weekly') {
            return [$anchor->copy()->startOfWeek()->toDateString(), $anchor->copy()->endOfWeek()->toDateString()];
        }

        return [$anchor->copy()->startOfMonth()->toDateString(), $anchor->copy()->endOfMonth()->toDateString()];
    }
}
