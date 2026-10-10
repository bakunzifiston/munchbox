<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     phone: string,
     *     email?: string|null,
     *     fulfillment_type: string,
     *     payment_method: string,
     *     notes?: string|null,
     *     address?: array{street: string, city?: string, district?: string, landmark?: string, label?: string}|null,
     *     address_id?: int|null
     * }  $data
     */
    public function createFromCart(?User $user, array $data): Order
    {
        if ($this->cart->isEmpty()) {
            throw new RuntimeException('Your cart is empty.');
        }

        if (blank($data['phone'] ?? null)) {
            throw new InvalidArgumentException('Phone number is required to place an order.');
        }

        return DB::transaction(function () use ($user, $data) {
            $customer = $this->resolveCustomer($user, $data);
            $fulfillment = FulfillmentType::from($data['fulfillment_type']);
            $deliveryFee = $fulfillment === FulfillmentType::Delivery
                ? Money::from(BusinessSetting::getValue('delivery_fee', 1500))
                : 0;

            $summary = $this->cart->summary($deliveryFee);

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'fulfillment_type' => $fulfillment,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['email'] ?? $customer->email,
                'subtotal' => $summary['subtotal'],
                'delivery_fee' => $deliveryFee,
                'discount' => 0,
                'tax' => 0,
                'total' => $summary['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($summary['items'] as $cartItem) {
                $orderItem = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem['product_id'],
                    'product_name' => $cartItem['name'],
                    'quantity' => $cartItem['quantity'],
                    'unit_price' => $cartItem['unit_price'],
                    'options_total' => $cartItem['options_total'],
                    'line_total' => $cartItem['line_total'],
                    'notes' => $cartItem['notes'] ?? null,
                ]);

                foreach ($cartItem['options'] as $option) {
                    OrderItemOption::query()->create([
                        'order_item_id' => $orderItem->id,
                        'product_option_id' => $option['id'],
                        'option_name' => $option['name'],
                        'option_group_name' => $option['group'],
                        'option_type' => $option['type'],
                        'price_adjustment' => $option['price_adjustment'],
                    ]);
                }
            }

            if ($fulfillment === FulfillmentType::Delivery) {
                $address = $this->resolveAddress($customer, $data);
                $street = $address?->street
                    ?? ($data['address']['street'] ?? null)
                    ?? ($data['address']['landmark'] ?? null)
                    ?? 'Kigali';

                Delivery::query()->create([
                    'order_id' => $order->id,
                    'address_id' => $address?->id,
                    'address_line' => $street,
                    'city' => $address?->city ?? ($data['address']['city'] ?? 'Kigali'),
                    'district' => $address?->district ?? ($data['address']['district'] ?? null),
                    'landmark' => $address?->landmark ?? ($data['address']['landmark'] ?? null),
                    'status' => 'pending',
                    'estimated_minutes' => (int) BusinessSetting::getValue('delivery_eta_minutes', 45),
                ]);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => PaymentMethod::from($data['payment_method']),
                'amount' => $summary['total'],
                'status' => PaymentStatus::Pending,
            ]);

            $this->recordStatus($order, null, OrderStatus::Pending, $user, 'Order placed');

            $this->cart->clear();

            return $order->fresh(['items.options', 'delivery', 'payments', 'customer']);
        });
    }

    public function transition(Order $order, OrderStatus $to, ?User $user = null, ?string $notes = null, ?string $rejectionReason = null): Order
    {
        return DB::transaction(function () use ($order, $to, $user, $notes, $rejectionReason) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $order->status;

            if (! $from->canTransitionTo($to)) {
                throw new InvalidArgumentException("Cannot move order from {$from->label()} to {$to->label()}.");
            }

            $payload = ['status' => $to];

            $payload = match ($to) {
                OrderStatus::Confirmed => array_merge($payload, ['confirmed_at' => now()]),
                OrderStatus::Preparing => array_merge($payload, ['preparing_at' => now()]),
                OrderStatus::Ready => array_merge($payload, ['ready_at' => now()]),
                OrderStatus::OutForDelivery => array_merge($payload, ['out_for_delivery_at' => now()]),
                OrderStatus::Delivered => array_merge($payload, ['delivered_at' => now()]),
                OrderStatus::Cancelled => array_merge($payload, ['cancelled_at' => now()]),
                OrderStatus::Rejected => array_merge($payload, [
                    'cancelled_at' => now(),
                    'rejection_reason' => $rejectionReason,
                ]),
                default => $payload,
            };

            $order->update($payload);

            if ($order->delivery && in_array($to, [OrderStatus::OutForDelivery, OrderStatus::Delivered], true)) {
                $order->delivery->update([
                    'status' => $to === OrderStatus::Delivered ? 'delivered' : 'out_for_delivery',
                    'delivered_at' => $to === OrderStatus::Delivered ? now() : null,
                ]);
            }

            $deductOn = BusinessSetting::getValue('inventory_deduct_on_status', 'preparing');
            if ($to->value === $deductOn || ($deductOn === 'confirmed' && $to === OrderStatus::Confirmed)) {
                $this->inventory->deductForOrder($order->fresh(), $user);
            }

            if ($to === OrderStatus::Delivered) {
                $order->payments()->where('status', PaymentStatus::Pending)->update([
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                ]);
            }

            $this->recordStatus($order, $from, $to, $user, $notes);

            return $order->fresh(['items.options', 'delivery', 'payments', 'statusHistories']);
        });
    }

    private function resolveCustomer(?User $user, array $data): Customer
    {
        if ($user) {
            $user->forceFill([
                'name' => $data['name'],
                'phone' => $data['phone'],
            ])->save();

            $customer = $user->customer;

            if (! $customer) {
                $customer = Customer::query()->create([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $data['email'] ?? $user->email,
                    'phone' => $data['phone'],
                ]);
            } else {
                $customer->update([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? $customer->email,
                    'phone' => $data['phone'],
                ]);
            }

            return $customer;
        }

        return Customer::query()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
        ]);
    }

    private function resolveAddress(Customer $customer, array $data): ?Address
    {
        if (! empty($data['address_id'])) {
            return $customer->addresses()->whereKey($data['address_id'])->first();
        }

        $street = trim((string) ($data['address']['street'] ?? ''));

        if ($street === '') {
            return null;
        }

        return $customer->addresses()->create([
            'label' => $data['address']['label'] ?? 'Delivery',
            'street' => $street,
            'city' => $data['address']['city'] ?? 'Kigali',
            'district' => $data['address']['district'] ?? null,
            'landmark' => $data['address']['landmark'] ?? null,
            'is_default' => $customer->addresses()->count() === 0,
        ]);
    }

    private function recordStatus(Order $order, ?OrderStatus $from, OrderStatus $to, ?User $user, ?string $notes): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'user_id' => $user?->id,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'MB-'.now()->format('ymd').'-'.strtoupper(substr(uniqid(), -5));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
