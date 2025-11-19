<?php

namespace App\Enums\Finance;

enum EntryPosition: string
{
    case Debit = 'DEBIT';
    case Credit = 'CREDIT';

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
