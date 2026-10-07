<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
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
            $order = Order::query()
                ->with(['items', 'statusHistories', 'delivery'])
                ->where('order_number', $request->string('order_number'))
                ->where('customer_phone', $request->string('phone'))
                ->first();

            if ($order) {
                session(['last_order_number' => $order->order_number]);
            }
        }

        return view('customer.orders.track', compact('order'));
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorizeView($order, request());

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

        if ($request->filled('phone') && $order->customer_phone === $request->string('phone')->toString()) {
            return;
        }

        abort(403);
    }
}
