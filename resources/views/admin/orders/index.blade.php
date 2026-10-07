@extends('layouts.admin')

@section('title', 'Orders')
@section('heading', 'Orders')

@section('content')
<form class="mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search order, name, phone" class="input-field max-w-xs">
    <select name="status" class="input-field max-w-[200px]">
        <option value="">All statuses</option>
        @foreach($statuses as $status)
            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <button class="btn-primary">Filter</button>
</form>

<div class="overflow-hidden rounded-3xl border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-cream/80 text-xs uppercase text-ink/40">
            <tr>
                <th class="px-4 py-3">Order</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-forest/5">
            @forelse($orders as $order)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-mono font-semibold">{{ $order->order_number }}</p>
                        <p class="text-xs text-ink/45">{{ $order->created_at->format('d M H:i') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p>{{ $order->customer_name }}</p>
                        <p class="text-xs text-ink/45">{{ $order->customer_phone }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $order->fulfillment_type->label() }}</td>
                    <td class="px-4 py-3"><span class="badge bg-sage text-munch">{{ $order->status->label() }}</span></td>
                    <td class="px-4 py-3 text-right font-semibold">{{ $order->formattedTotal() }}</td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-box">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-ink/40">No orders found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
