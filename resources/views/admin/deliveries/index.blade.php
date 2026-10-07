@extends('layouts.admin')

@section('title', 'Delivery')
@section('heading', 'Delivery')

@section('content')
<div class="mb-6 rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
    <h2 class="font-display text-lg font-bold text-munch">Ready / out for delivery</h2>
    <div class="mt-4 space-y-3">
        @forelse($readyForDelivery as $order)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-cream/70 px-4 py-3 text-sm">
                <div>
                    <p class="font-mono font-bold">{{ $order->order_number }}</p>
                    <p class="text-ink/60">{{ $order->customer_name }} · {{ $order->delivery?->fullAddress() }}</p>
                </div>
                <a href="{{ route('admin.orders.show', $order) }}" class="btn-secondary !px-3 text-xs">Manage</a>
            </div>
        @empty
            <p class="text-sm text-ink/40">No active delivery orders.</p>
        @endforelse
    </div>
</div>

<div class="space-y-4">
    @foreach($deliveries as $delivery)
        <form method="POST" action="{{ route('admin.deliveries.update', $delivery) }}" class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
            @csrf @method('PATCH')
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono font-bold text-munch">{{ $delivery->order->order_number }}</p>
                    <p class="mt-1 text-sm text-ink/60">{{ $delivery->fullAddress() }}</p>
                </div>
                <span class="badge bg-sage text-munch">{{ $delivery->order->status->label() }}</span>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-4">
                <input name="driver_name" value="{{ $delivery->driver_name }}" class="input-field" placeholder="Driver name">
                <input name="driver_phone" value="{{ $delivery->driver_phone }}" class="input-field" placeholder="Driver phone">
                <input type="number" name="estimated_minutes" value="{{ $delivery->estimated_minutes }}" class="input-field" placeholder="ETA min">
                <button class="btn-primary">Save</button>
            </div>
        </form>
    @endforeach
</div>
<div class="mt-4">{{ $deliveries->links() }}</div>
@endsection
