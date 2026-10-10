@extends('layouts.admin')

@section('title', 'Orders')
@section('heading', 'Orders')
@section('subheading', 'Confirm, prepare, and fulfill customer orders')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

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

<div class="overflow-hidden rounded-[28px] border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-[#FAF8F4] text-[11px] font-bold uppercase tracking-wider text-ink/40">
            <tr>
                <th class="px-5 py-3">Order</th>
                <th class="px-5 py-3">Customer</th>
                <th class="px-5 py-3">Type</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-right">Total</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-munch/8">
            @forelse($orders as $order)
                <tr class="transition hover:bg-[#FAF8F4]/70">
                    <td class="px-5 py-4">
                        <p class="font-mono font-bold text-munch">{{ $order->order_number }}</p>
                        <p class="text-xs text-ink/45">{{ $order->created_at->format('d M · H:i') }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-munch">{{ $order->customer_name }}</p>
                        <p class="text-xs text-ink/45">{{ $order->customer_phone }}</p>
                    </td>
                    <td class="px-5 py-4 text-ink/70">{{ $order->fulfillment_type->label() }}</td>
                    <td class="px-5 py-4"><x-admin.status-badge :status="$order->status" /></td>
                    <td class="px-5 py-4 text-right font-bold text-munch">{{ $order->formattedTotal() }}</td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-box hover:underline">Open</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-14 text-center text-ink/40">No orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
