<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Wording shown to customers, which depends on delivery vs pickup.
     */
    public function customerLabel(FulfillmentType $fulfillment = FulfillmentType::Delivery): string
    {
        $pickup = $fulfillment === FulfillmentType::Pickup;

        return match ($this) {
            self::Pending => 'Order placed',
            self::Ready => $pickup ? 'Ready for pickup' : 'Ready for dispatch',
            self::Delivered => $pickup ? 'Picked up' : 'Delivered',
            default => $this->label(),
        };
    }

    /**
     * The happy-path steps a customer sees in the order tracker.
     *
     * @return list<self>
     */
    public static function customerSteps(FulfillmentType $fulfillment): array
    {
        return $fulfillment === FulfillmentType::Pickup
            ? [self::Pending, self::Confirmed, self::Preparing, self::Ready, self::Delivered]
            : [self::Pending, self::Confirmed, self::Preparing, self::Ready, self::OutForDelivery, self::Delivered];
    }

    /**
     * Button text for moving an order into this status.
     */
    public function actionLabel(FulfillmentType $fulfillment = FulfillmentType::Delivery): string
    {
        return match ($this) {
            self::Confirmed => 'Confirm order',
            self::Preparing => 'Start preparing',
            self::Ready => $fulfillment === FulfillmentType::Pickup ? 'Ready for pickup' : 'Ready for dispatch',
            self::OutForDelivery => 'Send out for delivery',
            self::Delivered => $fulfillment === FulfillmentType::Pickup ? 'Mark picked up' : 'Mark delivered',
            default => $this->label(),
        };
    }

    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Pending => 'created_at',
            self::Confirmed => 'confirmed_at',
            self::Preparing => 'preparing_at',
            self::Ready => 'ready_at',
            self::OutForDelivery => 'out_for_delivery_at',
            self::Delivered => 'delivered_at',
            self::Cancelled, self::Rejected => 'cancelled_at',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::Preparing => 'indigo',
            self::Ready => 'teal',
            self::OutForDelivery => 'purple',
            self::Delivered => 'green',
            self::Cancelled => 'gray',
            self::Rejected => 'red',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Rejected], true);
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Rejected, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Ready, self::Cancelled],
            self::Ready => [self::OutForDelivery, self::Delivered, self::Cancelled],
            self::OutForDelivery => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Accepted and being prepared or fulfilled (excludes pending and finished orders).
     *
     * @return list<self>
     */
    public static function inProgress(): array
    {
        return [self::Confirmed, self::Preparing, self::Ready, self::OutForDelivery];
    }

    /**
     * Orders that count towards sales: everything except cancelled and rejected.
     *
     * @return list<string>
     */
    public static function excludedFromSales(): array
    {
        return [self::Cancelled->value, self::Rejected->value];
    }

    /**
     * Open orders: pending or in progress.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [
            self::Pending,
            self::Confirmed,
            self::Preparing,
            self::Ready,
            self::OutForDelivery,
        ];
    }
}
