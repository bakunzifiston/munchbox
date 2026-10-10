@extends('layouts.admin')

@section('title', 'Delivery')
@section('heading', 'Delivery')
@section('subheading', 'Assign drivers and track active deliveries')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<section class="mb-6 rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-extrabold text-munch">Ready / out for delivery</h2>
            <p class="mt-0.5 text-sm text-ink/45">Orders that need a driver now</p>
        </div>
    </div>
    <div class="mt-4 space-y-3">
        @forelse($readyForDelivery as $order)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[#F7F5F1] px-4 py-3 text-sm">
                <div>
                    <p class="font-mono font-bold text-munch">{{ $order->order_number }}</p>
                    <p class="text-ink/60">{{ $order->customer_name }} · {{ $order->delivery?->fullAddress() }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-admin.status-badge :status="$order->status" />
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-secondary !px-3 text-xs">Manage</a>
                </div>
            </div>
        @empty
            <p class="text-sm text-ink/40">No active delivery orders waiting.</p>
        @endforelse
    </div>
</section>

<div class="space-y-4">
    @forelse($deliveries as $delivery)
        <form method="POST" action="{{ route('admin.deliveries.update', $delivery) }}" class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
            @csrf @method('PATCH')
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono font-bold text-munch">{{ $delivery->order->order_number }}</p>
                    <p class="mt-1 text-sm text-ink/60">{{ $delivery->fullAddress() }}</p>
                    <p class="mt-1 text-xs text-ink/40">{{ $delivery->order->customer_name }} · {{ $delivery->order->customer_phone }}</p>
                </div>
                <x-admin.status-badge :status="$delivery->order->status" />
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-4">
                <div>
                    <label class="label-field">Driver name</label>
                    <input name="driver_name" value="{{ $delivery->driver_name }}" class="input-field" placeholder="Driver name">
                </div>
                <div>
                    <label class="label-field">Driver phone</label>
                    <input name="driver_phone" value="{{ $delivery->driver_phone }}" class="input-field" placeholder="Driver phone">
                </div>
                <div>
                    <label class="label-field">ETA (min)</label>
                    <input type="number" name="estimated_minutes" value="{{ $delivery->estimated_minutes }}" class="input-field" placeholder="45">
                </div>
                <div class="flex items-end">
                    <button class="btn-primary w-full">Save</button>
                </div>
            </div>
        </form>
    @empty
        <div class="rounded-[28px] border border-dashed border-munch/20 p-12 text-center text-ink/40">No open deliveries.</div>
    @endforelse
</div>
<div class="mt-4">{{ $deliveries->links() }}</div>
@endsection
