<?php

namespace App\Enums;

enum NotificationTypes: string
{
case OrderPlaced = 'order_placed';
case OrderShipped = 'order_shipped';
case OrderDelivered = 'order_delivered';
case OrderCancelled = 'order_cancelled';
case OrderReturned = 'order_returned';

case PaymentSuccessful = 'payment_successful';
case PaymentFailed = 'payment_failed';
case RefundProcessed = 'refund_processed';

case NewCoupon = 'new_coupon';
case CouponExpiring = 'coupon_expiring';
case DiscountAvailable = 'discount_available';

case ProfileUpdated = 'profile_updated';
case PasswordChanged = 'password_changed';
case NewLogin = 'new_login';

case AdminAnnouncement = 'admin_announcement';
case Warning = 'warning';
case Info = 'info';

    public function label(){
        return match ($this) {
        self::OrderPlaced => 'Order Placed',
        self::OrderShipped => 'Order Shipped',
        self::OrderDelivered => 'Order Delivered',
        self::OrderCancelled => 'Order Cancelled',
        self::OrderReturned => 'Order Returned',

        self::PaymentSuccessful => 'Payment Successful',
        self::PaymentFailed => 'Payment Failed',
        self::RefundProcessed => 'Refund Processed',

        self::NewCoupon => 'New Coupon Available',
        self::CouponExpiring => 'Coupon Expiring Soon',
        self::DiscountAvailable => 'Discount Available',

        self::ProfileUpdated => 'Profile Updated',
        self::PasswordChanged => 'Password Changed',
        self::NewLogin => 'New Login Detected',

        self::AdminAnnouncement => 'Admin Announcement',
        self::Warning => 'Warning',
        self::Info => 'Information',

        default => 'Unknown Notification Type'
        };
    }

    public function category(){
        return match ($this) {
        self::OrderPlaced,
        self::OrderShipped,
        self::OrderDelivered,
        self::OrderCancelled,
        self::OrderReturned => 'Order',

        self::PaymentSuccessful,
        self::PaymentFailed,
        self::RefundProcessed => 'Payment',

        self::NewCoupon,
        self::CouponExpiring,
        self::DiscountAvailable => 'Promotion',

        self::ProfileUpdated,
        self::PasswordChanged,
        self::NewLogin => 'Account',

        self::AdminAnnouncement,
        self::Warning,
        self::Info => 'System',
    };
    }
}