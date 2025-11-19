<?php

namespace App\Enums\Finance;

enum TransactionCategory: string
{
    case Income = 'INCOME';
    case Expense = 'EXPENSE';
    case Transfer = 'TRANSFER';
    case Billing = 'BILLING';
    case Payroll = 'PAYROLL';
    case Asset = 'ASSET';
    case Liability = 'LIABILITY';

    /**
     * Get all enum values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
