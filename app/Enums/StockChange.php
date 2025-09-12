<?php

namespace App\Enums;

enum StockChange: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Restock = 'restock';
    case Cancellation = 'cancellation';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Sale',
            self::Purchase => 'Purchase',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::Restock => 'Restock',
            self::Cancellation => 'Cancellation',
            self::Refund => 'Refund',
        };
    }
}
