@extends('layouts.admin')

@section('title', $customer->name)
@section('heading', $customer->name)

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm text-sm">
        <p><strong>Phone:</strong> {{ $customer->phone }}</p>
        <p class="mt-2"><strong>Email:</strong> {{ $customer->email ?? '—' }}</p>
        <h3 class="mt-5 font-semibold text-munch">Addresses</h3>
        <ul class="mt-2 space-y-2">
            @forelse($customer->addresses as $address)
                <li class="rounded-xl bg-cream/80 px-3 py-2">{{ $address->fullAddress() }}</li>
            @empty
                <li class="text-ink/40">No addresses</li>
            @endforelse
        </ul>
    </div>
    <div class="lg:col-span-2 rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
        <h2 class="font-display text-lg font-bold text-munch">Orders</h2>
        <ul class="mt-4 space-y-3">
            @forelse($customer->orders as $order)
                <li class="flex items-center justify-between text-sm">
                    <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-munch">{{ $order->order_number }}</a>
                    <span>{{ $order->status->label() }} · {{ $order->formattedTotal() }}</span>
                </li>
            @empty
                <li class="text-ink/40">No orders</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
