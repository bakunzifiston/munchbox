<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FulfillmentType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckoutRequest;
use App\Models\BusinessSetting;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function create(): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty.');
        }

        $deliveryFee = (float) BusinessSetting::getValue('delivery_fee', 1500);
        $user = auth()->user();
        $customer = $user?->customer;
        $addresses = $customer?->addresses ?? collect();

        return view('customer.checkout.create', array_merge(
            $this->cart->summary($deliveryFee),
            [
                'deliveryFee' => $deliveryFee,
                'addresses' => $addresses,
                'user' => $user,
                'fulfillmentTypes' => FulfillmentType::cases(),
                'paymentMethods' => PaymentMethod::cases(),
            ]
        ));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty.');
        }

        try {
            $order = $this->orders->createFromCart($request->user(), $request->validated());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        session(['last_order_number' => $order->order_number]);

        return redirect()
            ->route('orders.confirmation', $order)
            ->with('success', 'Order placed successfully!');
    }
}
