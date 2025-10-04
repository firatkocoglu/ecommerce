<?php

namespace App\Enums;

enum CartStatus: string
{
    case ACTIVE = 'active';
    case MERGED = 'merged';
    case ABANDONED = 'abandoned';
    case ORDERED = 'ordered';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::MERGED => 'Merged',
            self::ABANDONED => 'Abandoned',
            self::ORDERED => 'Ordered',
        };
    }
}
