<?php

namespace App\Enums;

enum TransactionStatus: string
{
    // Common statuses for both "purchase" and "sell"
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    // Specific statuses for "purchase"
    case APPROVED = 'approved';
    case RECEIVED = 'received';
    case PARTIALLY_RECEIVED = 'partially_received';
    case REJECTED = 'rejected';
    case REFUNDED = 'refunded';
    case BACKORDERED = 'backordered';

    // Specific statuses for "sell"
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case PARTIALLY_DELIVERED = 'partially_delivered';
    case RETURNED = 'returned';

    /**
     * Map status to human-readable labels
     */
    public function label(): string
    {
        return match ($this) {
            // Common statuses
            self::PENDING => __('Pending'),
            self::PROCESSING => __('Processing'),
            self::COMPLETED => __('Completed'),
            self::CANCELLED => __('Cancelled'),

            // Purchase-specific statuses
            self::APPROVED => __('Approved'),
            self::RECEIVED => __('Received'),
            self::PARTIALLY_RECEIVED => __('Partially Received'),
            self::REJECTED => __('Rejected'),
            self::REFUNDED => __('Refunded'),
            self::BACKORDERED => __('Backordered'),

            // Sell-specific statuses
            self::PAID => __('Paid'),
            self::SHIPPED => __('Shipped'),
            self::DELIVERED => __('Delivered'),
            self::PARTIALLY_DELIVERED => __('Partially Delivered'),
            self::RETURNED => __('Returned'),
        };
    }

    /**
     * Get all statuses for "purchase"
     */
    public static function purchaseStatuses(): array
    {
        return array_map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], [
            self::PENDING,
            self::PROCESSING,
            self::APPROVED,
            self::RECEIVED,
            self::PARTIALLY_RECEIVED,
            self::REJECTED,
            self::REFUNDED,
            self::BACKORDERED,
            self::COMPLETED,
            self::CANCELLED,
        ]);
    }

    /**
     * Get all statuses for "sell"
     */
    public static function sellStatuses(): array
    {
        return array_map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], [
            self::PENDING,
            self::PROCESSING,
            self::PAID,
            self::SHIPPED,
            self::DELIVERED,
            self::PARTIALLY_DELIVERED,
            self::RETURNED,
            self::COMPLETED,
            self::CANCELLED,
            self::REFUNDED,
        ]);
    }

    /**
     * Get all statuses
     */
    public static function allStatuses(): array
    {
        return array_map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
