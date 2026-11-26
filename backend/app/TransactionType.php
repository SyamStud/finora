<?php

namespace App;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function sign(): int
    {
        return match ($this) {
            self::Income => 1,
            self::Expense => -1,
        };
    }
}
