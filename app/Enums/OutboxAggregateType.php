<?php

namespace App\Enums;

enum OutboxAggregateType: string
{
    case USER = 'user';
    case ORDER = 'order';
    case PRODUCT = 'product';
    case STOCK = 'stock';
    case CART = 'cart';
    case PAYMENT = 'payment';

    // Get allowed event types for the aggregate
    // Avoid e.g. ORDER -> PASSWORD_RESET
    public function allowedEventTypes(): array
    {
        return match ($this) {
            self::USER => [
                OutboxEventType::EMAIL_VERIFICATION,
                OutboxEventType::USER_REGISTRATION,
                OutboxEventType::PASSWORD_RESET,
                OutboxEventType::USER_LOGIN,
                OutboxEventType::USER_LOGOUT,
            ],
            self::ORDER => [
                OutboxEventType::ORDER_PLACED,
                OutboxEventType::ORDER_SHIPPED,
                OutboxEventType::ORDER_DELIVERED,
            ],
            self::PRODUCT => [
                OutboxEventType::PRODUCT_REVIEW,
            ],
            default => [],
        };
    }
}
