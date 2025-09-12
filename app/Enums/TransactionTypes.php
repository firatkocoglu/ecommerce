<?php

namespace App\Enums;

enum TransactionTypes: string
{
    case Payment = 'payment';
    case Refund = 'refund';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Payment',
            self::Refund => 'Refund',
            self::Transfer => 'transfer',
            self::Adjustment => 'Adjustment',
        };
    }
}
