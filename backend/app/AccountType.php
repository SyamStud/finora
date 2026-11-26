<?php

namespace App;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case EWallet = 'e_wallet';
    case PersonalWallet = 'personal_wallet';
    case Savings = 'savings';
    case InvestmentAccount = 'investment_account';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
