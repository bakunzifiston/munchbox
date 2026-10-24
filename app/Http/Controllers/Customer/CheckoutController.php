<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FulfillmentType;
use App\Enums\PaymentMethod;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckoutRequest;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const TOKEN_KEY = 'checkout_token';

    private const COMPLETED_TOKEN_KEY = 'checkout_completed_token';

    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function create(): View|RedirectResponse
    {
        $notices = $this->cart->refresh();

        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', $notices[0] ?? 'Your cart is empty.');
        }

        $minOrder = (float) BusinessSetting::getValue('min_order_amount', 0);

        if ($this->cart->subtotal() < $minOrder) {
            return redirect()->route('cart.index')->with('error', 'The minimum order is '.Money::format($minOrder).'.');
        }

        // One token per checkout page; the order is placed at most once per token.
        session([self::TOKEN_KEY => (string) Str::uuid()]);

        $deliveryFee = (float) BusinessSetting::getValue('delivery_fee', 0);
        $user = auth()->user();
        $addresses = $user?->customer?->addresses ?? collect();

        return view('customer.checkout.create', array_merge(
            $this->cart->summary($deliveryFee),
            [
                'deliveryFee' => $deliveryFee,
                'addresses' => $addresses,
                'user' => $user,
                'notices' => $notices,
                'checkoutToken' => session(self::TOKEN_KEY),
                'deliveryAreas' => config('menu.delivery_areas', []),
                'fulfillmentTypes' => FulfillmentType::cases(),
                'paymentMethods' => PaymentMethod::cases(),
            ]
        ));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $token = $request->validated('checkout_token');

        // A repeated submit of a form that already placed an order: show that order again.
        if ($token === session(self::COMPLETED_TOKEN_KEY) && ($existing = $this->lastOrder())) {
            return redirect()
                ->route('orders.confirmation', $existing)
                ->with('success', 'Your order was already placed.');
        }

        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty.');
        }

        if ($token !== session(self::TOKEN_KEY)) {
            return redirect()
                ->route('checkout.create')
                ->withInput()
                ->with('error', 'Your checkout page was out of date. Please review your order and place it again.');
        }

        $notices = $this->cart->refresh();

        if ($notices !== []) {
            return redirect()
                ->route($this->cart->isEmpty() ? 'menu.index' : 'checkout.create')
                ->withInput()
                ->with('error', implode(' ', $notices).' Please review your order before placing it.');
        }

        $minOrder = (float) BusinessSetting::getValue('min_order_amount', 0);

        if ($this->cart->subtotal() < $minOrder) {
            return redirect()->route('cart.index')->with('error', 'The minimum order is '.Money::format($minOrder).'.');
        }

        session()->forget(self::TOKEN_KEY);

        try {
            $order = $this->orders->createFromCart($request->user(), $request->validated());
        } catch (CheckoutException $e) {
            session([self::TOKEN_KEY => $token]);

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            session([self::TOKEN_KEY => $token]);

            return back()->withInput()->with('error', 'We couldn’t place your order. Please try again, or call us if it keeps happening.');
        }

        session([
            'last_order_number' => $order->order_number,
            self::COMPLETED_TOKEN_KEY => $token,
        ]);

        return redirect()
            ->route('orders.confirmation', $order)
            ->with('success', 'Order placed successfully!');
    }

    private function lastOrder(): ?Order
    {
        $number = session('last_order_number');

        return $number ? Order::query()->where('order_number', $number)->first() : null;
    }
}
