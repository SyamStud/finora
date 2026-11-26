<?php

namespace App;

enum AssetType: string
{
    case MutualFund = 'mutual_fund';
    case Gold = 'gold';
    case Stock = 'stock';
    case Deposit = 'deposit';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
