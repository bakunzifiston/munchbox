<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Received = 'received';
    case Used = 'used';
    case Adjustment = 'adjustment';
    case Waste = 'waste';
    case OrderDeduction = 'order_deduction';
    case OrderReversal = 'order_reversal';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Stock Received',
            self::Used => 'Stock Used',
            self::Adjustment => 'Adjustment',
            self::Waste => 'Waste',
            self::OrderDeduction => 'Order Deduction',
            self::OrderReversal => 'Order Reversal',
        };
    }
}
