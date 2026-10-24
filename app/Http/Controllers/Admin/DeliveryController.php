<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(): View
    {
        $deliveries = Delivery::query()
            ->with('order.latestPayment')
            ->whereHas('order', fn ($q) => $q->where('fulfillment_type', FulfillmentType::Delivery)
                ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Rejected, OrderStatus::Delivered]))
            ->latest()
            ->paginate(20);

        $readyForDelivery = Order::query()
            ->with('delivery')
            ->where('fulfillment_type', FulfillmentType::Delivery)
            ->whereIn('status', [OrderStatus::Ready, OrderStatus::OutForDelivery])
            ->latest()
            ->get();

        $activeDeliveries = Delivery::query()
            ->whereHas('order', fn ($q) => $q->where('fulfillment_type', FulfillmentType::Delivery)
                ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Rejected, OrderStatus::Delivered]))
            ->count();

        $outForDelivery = Order::query()
            ->where('fulfillment_type', FulfillmentType::Delivery)
            ->where('status', OrderStatus::OutForDelivery)
            ->count();

        $unassigned = Delivery::query()
            ->where(fn ($q) => $q->whereNull('driver_name')->orWhere('driver_name', ''))
            ->whereHas('order', fn ($q) => $q->whereIn('status', [OrderStatus::Ready, OrderStatus::OutForDelivery]))
            ->count();

        $kpis = [
            ['label' => 'Active', 'value' => $activeDeliveries, 'hint' => 'Open delivery orders'],
            ['label' => 'Ready', 'value' => $readyForDelivery->where('status', OrderStatus::Ready)->count(), 'hint' => 'Waiting for driver', 'tone' => 'warn'],
            ['label' => 'Out for delivery', 'value' => $outForDelivery, 'hint' => 'On the road', 'tone' => 'info'],
            ['label' => 'No driver', 'value' => $unassigned, 'hint' => 'Need assignment', 'tone' => $unassigned > 0 ? 'danger' : 'default'],
        ];

        return view('admin.deliveries.index', compact('deliveries', 'readyForDelivery', 'kpis'));
    }

    public function update(Request $request, Delivery $delivery): RedirectResponse
    {
        $data = $request->validate([
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'estimated_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Delivery status follows the order's status workflow, so it isn't editable here.
        if ($delivery->order && $delivery->order->status->isTerminal()) {
            return back()->with('error', 'This order is already finished, so its delivery can’t be changed.');
        }

        $delivery->update($data);

        return back()->with('success', 'Delivery details saved.');
    }
}
