<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\OrderNumber;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['items', 'latestPayment'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', compact('orders'));
    }

    public function confirmation(Order $order): View
    {
        $this->authorizeView($order, request());

        $order->load(['items.options', 'delivery', 'payments', 'statusHistories']);

        return view('customer.orders.confirmation', compact('order'));
    }

    public function show(Order $order, Request $request): View
    {
        $this->authorizeView($order, $request);

        $order->load(['items.options', 'delivery', 'payments', 'statusHistories.user']);

        return view('customer.orders.show', compact('order'));
    }

    public function track(Request $request): View
    {
        $order = null;

        if ($request->filled('order_number') && $request->filled('phone')) {
            $order = $this->findTrackableOrder(
                $request->string('order_number')->toString(),
                $request->string('phone')->toString(),
            );

            if ($order) {
                session(['last_order_number' => $order->order_number]);
            }
        }

        return view('customer.orders.track', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id !== null && $order->user_id === $request->user()->id, 403);

        if ($order->status !== OrderStatus::Pending) {
            return back()->with('error', 'Only pending orders can be cancelled.');
        }

        try {
            $this->orders->transition($order, OrderStatus::Cancelled, auth()->user(), 'Cancelled by customer');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order cancelled.');
    }

    private function findTrackableOrder(string $orderNumber, string $phone): ?Order
    {
        $normalized = OrderNumber::normalize($orderNumber);

        if ($normalized === '' || Phone::digits($phone) === '') {
            return null;
        }

        return Order::query()
            ->with(['items.options', 'statusHistories', 'delivery', 'payments'])
            ->whereRaw('UPPER(order_number) = ?', [$normalized])
            ->get()
            ->first(fn (Order $order) => Phone::matches($order->customer_phone, $phone));
    }

    private function authorizeView(Order $order, Request $request): void
    {
        $user = $request->user();

        if ($user?->isStaff()) {
            return;
        }

        if ($user && $order->user_id === $user->id) {
            return;
        }

        if (session('last_order_number') === $order->order_number) {
            return;
        }

        if ($request->filled('phone') && Phone::matches($order->customer_phone, $request->string('phone')->toString())) {
            return;
        }

        abort(403);
    }
}
