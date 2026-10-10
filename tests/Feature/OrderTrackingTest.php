<?php

namespace Tests\Feature;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_track_order_with_order_number_and_phone(): void
    {
        $order = $this->makeOrder(phone: '+250788994400');

        $response = $this->get(route('orders.track', [
            'order_number' => $order->order_number,
            'phone' => '+250788994400',
        ]));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('Pending');
    }

    public function test_tracking_accepts_phone_with_different_formatting(): void
    {
        $order = $this->makeOrder(phone: '+250788994400');

        $response = $this->get(route('orders.track', [
            'order_number' => strtolower($order->order_number),
            'phone' => '0788 994 400',
        ]));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertDontSee('No order found');
    }

    public function test_tracking_rejects_wrong_phone(): void
    {
        $order = $this->makeOrder(phone: '+250788994400');

        $response = $this->get(route('orders.track', [
            'order_number' => $order->order_number,
            'phone' => '+250700000000',
        ]));

        $response->assertOk();
        $response->assertSee('No order found');
    }

    public function test_confirmation_is_accessible_via_session_after_order(): void
    {
        $order = $this->makeOrder();

        $response = $this->withSession(['last_order_number' => $order->order_number])
            ->get(route('orders.confirmation', $order));

        $response->assertOk();
        $response->assertSee('Order placed');
        $response->assertSee($order->order_number);
    }

    public function test_order_details_are_forbidden_without_access(): void
    {
        $order = $this->makeOrder();

        $this->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_order_details_are_accessible_with_matching_phone_query(): void
    {
        $order = $this->makeOrder(phone: '+250788994400');

        $response = $this->get(route('orders.show', [
            'order' => $order,
            'phone' => '0788994400',
        ]));

        $response->assertOk();
        $response->assertSee('Progress');
        $response->assertSee('Pending');
    }

    public function test_admin_status_progress_is_visible_when_tracking(): void
    {
        $order = $this->makeOrder(phone: '+250788994400');

        app(OrderService::class)->transition($order, OrderStatus::Confirmed);
        app(OrderService::class)->transition($order->fresh(), OrderStatus::Preparing);

        $response = $this->withSession(['last_order_number' => $order->order_number])
            ->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('Preparing');
    }

    public function test_owner_can_view_order_when_signed_in(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(user: $user, phone: '+250788994400');

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    private function makeOrder(?User $user = null, string $phone = '+250788994400'): Order
    {
        $customer = Customer::query()->create([
            'user_id' => $user?->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => $phone,
        ]);

        return Order::query()->create([
            'order_number' => 'MB-261010-A60A4',
            'user_id' => $user?->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::Pending,
            'fulfillment_type' => FulfillmentType::Delivery,
            'customer_name' => 'Test Customer',
            'customer_phone' => $phone,
            'customer_email' => 'customer@example.com',
            'subtotal' => 14000,
            'delivery_fee' => 1500,
            'discount' => 0,
            'tax' => 0,
            'total' => 15500,
        ]);
    }
}
