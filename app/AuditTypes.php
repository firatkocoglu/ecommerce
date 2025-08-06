<?php

namespace App;

enum AuditTypes: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case FORCE_DELETE = 'force_delete';

    public function label(): string
    {
        return match ($this) {
            self::CREATE => 'Created',
            self::UPDATE => 'Updated',
            self::DELETE => 'Deleted',
            self::RESTORE => 'Restored',
            self::FORCE_DELETE => 'Force Deleted',
        };
    }
}
