<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }
}
