<?php

namespace App\Enums;

enum OutboxEventType: string
{
// -------------------
    // User domain events
    // -------------------
    case EMAIL_VERIFICATION = 'email_verification';
    case USER_REGISTRATION = 'user_registration';
    case PASSWORD_RESET = 'password_reset';
    case USER_LOGIN = 'user_login';
    case USER_LOGOUT = 'user_logout';

    // -------------------
    // Order domain events
    // -------------------
    case ORDER_PLACED = 'order_placed';
    case ORDER_SHIPPED = 'order_shipped';
    case ORDER_DELIVERED = 'order_delivered';

    // -------------------
    // Product domain events
    // -------------------
    case PRODUCT_REVIEW = 'product_review';
}

