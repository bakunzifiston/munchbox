<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['customer', 'latestPayment', 'items'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->q, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('order_number', 'ilike', "%{$search}%")
                        ->orWhere('customer_name', 'ilike', "%{$search}%")
                        ->orWhere('customer_phone', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $todayRevenue = (float) Order::query()
            ->today()
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Rejected])
            ->sum('total');

        $kpis = [
            ['label' => 'Today', 'value' => Order::query()->today()->count(), 'hint' => 'Orders placed today'],
            ['label' => 'Pending', 'value' => Order::query()->pending()->count(), 'hint' => 'Need confirmation', 'tone' => 'warn'],
            ['label' => 'In kitchen', 'value' => Order::query()->active()->where('status', '!=', OrderStatus::Pending)->count(), 'hint' => 'Confirmed → out for delivery'],
            ['label' => 'Revenue today', 'value' => number_format($todayRevenue, 0).' RWF', 'hint' => 'Excludes cancelled', 'tone' => 'success'],
        ];

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'filters' => $request->only(['status', 'q']),
            'kpis' => $kpis,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'items.options', 'customer', 'delivery', 'payments', 'statusHistories.user']);

        return view('admin.orders.show', [
            'order' => $order,
            'transitions' => $order->status->allowedTransitions(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $status = OrderStatus::from($data['status']);

            if ($order->delivery && ($data['driver_name'] || $data['driver_phone'])) {
                $order->delivery->update([
                    'driver_name' => $data['driver_name'] ?? $order->delivery->driver_name,
                    'driver_phone' => $data['driver_phone'] ?? $order->delivery->driver_phone,
                ]);
            }

            $this->orders->transition(
                $order,
                $status,
                $request->user(),
                $data['notes'] ?? null,
                $data['rejection_reason'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order status updated.');
    }
}
