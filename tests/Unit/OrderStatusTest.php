<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_pending_can_move_to_confirmed_or_cancelled(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed));
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Cancelled));
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Rejected));
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered));
    }

    public function test_delivery_happy_path_transitions(): void
    {
        $this->assertTrue(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Preparing));
        $this->assertTrue(OrderStatus::Preparing->canTransitionTo(OrderStatus::Ready));
        $this->assertTrue(OrderStatus::Ready->canTransitionTo(OrderStatus::OutForDelivery));
        $this->assertTrue(OrderStatus::OutForDelivery->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Pending));
    }
}
