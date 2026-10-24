<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
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
     *     delivery_area?: string|null,
     *     address?: array{street?: string|null, landmark?: string|null, label?: string|null}|null,
     *     address_id?: int|null
     * }  $data
     */
    public function createFromCart(?User $user, array $data): Order
    {
        if ($this->cart->isEmpty()) {
            throw new CheckoutException('Your cart is empty.');
        }

        // Staff testing the storefront shouldn't have their own account overwritten with customer details.
        $owner = $user && ! $user->isStaff() ? $user : null;

        $order = $this->place($owner, $data, $this->cart->items()->all(), $user);
        $this->cart->clear();

        return $order;
    }

    /**
     * Create an order from priced lines (see CartService / MenuPricing for the line shape).
     *
     * @param  list<array>  $lines
     */
    public function place(?User $user, array $data, array $lines, ?User $placedBy = null, ?string $historyNote = 'Order placed'): Order
    {
        if ($lines === []) {
            throw new CheckoutException('Add at least one item to the order.');
        }

        if (blank($data['phone'] ?? null)) {
            throw new CheckoutException('Phone number is required to place an order.');
        }

        $shortages = $this->inventory->shortagesForCart($lines);

        if ($shortages !== []) {
            throw new CheckoutException(
                'Sorry, we’re running low on '.implode(', ', $shortages).' right now. Please adjust your order or call us.'
            );
        }

        return DB::transaction(function () use ($user, $data, $lines, $placedBy, $historyNote) {
            $customer = $this->resolveCustomer($user, $data);
            $fulfillment = FulfillmentType::from($data['fulfillment_type']);
            $deliveryFee = $fulfillment === FulfillmentType::Delivery
                ? Money::from(BusinessSetting::getValue('delivery_fee', 1500))
                : 0;

            $subtotal = Money::from(collect($lines)->sum('line_total'));
            $total = Money::from($subtotal + $deliveryFee);

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'fulfillment_type' => $fulfillment,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['email'] ?? $customer->email,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => 0,
                'tax' => 0,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $cartItem) {
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
                $area = $address?->district ?? ($data['delivery_area'] ?? null);
                $street = $address?->street
                    ?? ($data['address']['street'] ?? null)
                    ?? $area
                    ?? 'Kigali';

                Delivery::query()->create([
                    'order_id' => $order->id,
                    'address_id' => $address?->id,
                    'address_line' => $street,
                    'city' => $address?->city ?? 'Kigali',
                    'district' => $area,
                    'landmark' => $address?->landmark ?? ($data['address']['landmark'] ?? null),
                    'status' => 'pending',
                    'estimated_minutes' => (int) BusinessSetting::getValue('delivery_eta_minutes', 45),
                ]);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => PaymentMethod::from($data['payment_method']),
                'amount' => $total,
                'status' => PaymentStatus::Pending,
            ]);

            $this->recordStatus($order, null, OrderStatus::Pending, $placedBy ?? $user, $historyNote);

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

            if ($to === OrderStatus::OutForDelivery && $order->fulfillment_type === FulfillmentType::Pickup) {
                throw new InvalidArgumentException('Pickup orders aren’t sent out for delivery.');
            }

            $payload = ['status' => $to];

            $payload = match ($to) {
                OrderStatus::Confirmed => array_merge($payload, ['confirmed_at' => now()]),
                OrderStatus::Preparing => array_merge($payload, ['preparing_at' => now()]),
                OrderStatus::Ready => array_merge($payload, ['ready_at' => now()]),
                OrderStatus::OutForDelivery => array_merge($payload, ['out_for_delivery_at' => now()]),
                OrderStatus::Delivered => array_merge($payload, ['delivered_at' => now()]),
                OrderStatus::Cancelled => array_merge($payload, [
                    'cancelled_at' => now(),
                    'rejection_reason' => $rejectionReason,
                ]),
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

            // Stock already taken for an order that won't be made goes back, with its own movement record.
            if (in_array($to, [OrderStatus::Cancelled, OrderStatus::Rejected], true) && $order->inventory_deducted) {
                $this->inventory->restoreForOrder($order->fresh(), $user);
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

    /**
     * Mark the order's pending payment as paid (one payment record per order, so nothing is counted twice).
     */
    public function recordPayment(Order $order, PaymentMethod $method, ?string $reference, User $user): Payment
    {
        return DB::transaction(function () use ($order, $method, $reference, $user) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
                throw new InvalidArgumentException('Payments can’t be recorded for cancelled or rejected orders.');
            }

            if ($order->payments()->where('status', PaymentStatus::Paid)->exists()) {
                throw new InvalidArgumentException('This order is already marked as paid.');
            }

            $payment = $order->payments()->where('status', PaymentStatus::Pending)->latest('id')->first()
                ?? $order->payments()->make(['status' => PaymentStatus::Pending]);

            $payment->fill([
                'method' => $method,
                'amount' => $order->total,
                'status' => PaymentStatus::Paid,
                'reference' => $reference,
                'paid_at' => now(),
            ])->save();

            $this->recordStatus($order, $order->status, $order->status, $user, trim('Payment recorded: '.$method->label().' '.($reference ? "({$reference})" : '')));

            return $payment;
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
            'city' => 'Kigali',
            'district' => $data['delivery_area'] ?? null,
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
