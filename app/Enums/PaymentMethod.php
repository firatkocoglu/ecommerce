<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CreditCard = 'credit_card';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::CreditCard => 'Credit Card',
            self::BankTransfer => 'Bank Transfer',
        };
    }
}
