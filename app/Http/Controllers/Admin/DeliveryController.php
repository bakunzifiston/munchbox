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
            ->with('order')
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

        return view('admin.deliveries.index', compact('deliveries', 'readyForDelivery'));
    }

    public function update(Request $request, Delivery $delivery): RedirectResponse
    {
        $data = $request->validate([
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'estimated_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $delivery->update($data);

        return back()->with('success', 'Delivery updated.');
    }
}
