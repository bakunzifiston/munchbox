@extends('layouts.admin')

@section('title', 'Orders')
@section('heading', 'Orders')
@section('subheading', 'Search, filter and move orders through the kitchen')

@php
    use App\Enums\OrderStatus;
    use App\Support\Money;

    $current = $filters['status'] ?? null;
    $base = array_filter(\Illuminate\Support\Arr::except($filters, ['status']));
    $openCount = collect(OrderStatus::active())->sum(fn ($s) => (int) ($statusCounts[$s->value] ?? 0));
    $allCount = (int) $statusCounts->sum();
    $tabs = array_merge(
        [['key' => null, 'label' => 'All', 'count' => $allCount], ['key' => 'open', 'label' => 'Open', 'count' => $openCount]],
        array_map(fn ($s) => ['key' => $s->value, 'label' => $s->label(), 'count' => (int) ($statusCounts[$s->value] ?? 0)], $statuses),
    );
    $hasFilters = count(array_filter($filters)) > 0;
@endphp

@section('content')
<nav class="-mx-4 mb-4 flex gap-1.5 overflow-x-auto px-4 pb-1 scrollbar-none sm:mx-0 sm:px-0" aria-label="Order status">
    @foreach($tabs as $tab)
        <a href="{{ route('admin.orders.index', array_merge($base, $tab['key'] ? ['status' => $tab['key']] : [])) }}"
           class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition {{ $current === $tab['key'] ? 'bg-munch text-white' : 'bg-white text-munch ring-1 ring-munch/10 hover:ring-box/40' }}">
            {{ $tab['label'] }}
            <span class="{{ $current === $tab['key'] ? 'text-white/70' : 'text-ink/40' }}">{{ $tab['count'] }}</span>
        </a>
    @endforeach
    @if($current === 'in_progress')
        <span class="inline-flex shrink-0 items-center rounded-full bg-munch px-3.5 py-1.5 text-xs font-bold text-white">In progress</span>
    @endif
</nav>

<x-admin.panel>
    <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] lg:items-end">
        @if($current)<input type="hidden" name="status" value="{{ $current }}">@endif
        <div>
            <label for="q" class="label-field">Search</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input-field" placeholder="Order number, customer or phone">
        </div>
        <div>
            <label for="type" class="label-field">Type</label>
            <select id="type" name="type" class="input-field">
                <option value="">All</option>
                @foreach($fulfillmentTypes as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="payment" class="label-field">Payment</label>
            <select id="payment" name="payment" class="input-field">
                <option value="">All</option>
                @foreach($paymentStatuses as $paymentStatus)
                    <option value="{{ $paymentStatus->value }}" @selected(($filters['payment'] ?? null) === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from" class="label-field">From</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input-field">
        </div>
        <div>
            <label for="to" class="label-field">To</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input-field">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary flex-1 lg:flex-none">Filter</button>
            @if($hasFilters)
                <a href="{{ route('admin.orders.index') }}" class="btn-secondary">Reset</a>
            @endif
        </div>
    </form>
</x-admin.panel>

<x-admin.panel class="mt-4" :title="$orders->total().' '.Str::plural('order', $orders->total())" subtitle="Newest first · times in Kigali">
    @if($orders->isEmpty())
        <x-admin.empty-state
            :title="$hasFilters ? 'No orders match these filters' : 'No orders yet'"
            :description="$hasFilters ? 'Try a different search or clear the filters.' : 'Orders from the storefront and phone orders will appear here.'"
            :action-url="$hasFilters ? route('admin.orders.index') : null"
            :action-label="$hasFilters ? 'Clear filters' : null"
        />
    @else
        <div class="-mx-5 overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr><th>Order</th><th>Customer</th><th>Items</th><th>Type</th><th>Status</th><th>Payment</th><th class="text-right">Total</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-bold text-munch hover:text-box">{{ $order->order_number }}</a>
                                <p class="text-xs text-ink/45" title="{{ $order->created_at->diffForHumans() }}">{{ $order->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-munch">{{ $order->customer_name }}</p>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}" class="text-xs text-ink/50 hover:text-box">{{ $order->customer_phone }}</a>
                            </td>
                            <td class="whitespace-nowrap text-ink/70">{{ (int) $order->items_sum_quantity }} {{ Str::plural('unit', (int) $order->items_sum_quantity) }}</td>
                            <td class="text-ink/70">{{ $order->fulfillment_type->label() }}</td>
                            <td><x-admin.status-badge :status="$order->status" /></td>
                            <td class="whitespace-nowrap text-xs text-ink/60">
                                {{ $order->latestPayment?->method->label() ?? '—' }}
                                @if($order->latestPayment)
                                    <span class="block font-semibold {{ $order->latestPayment->status === \App\Enums\PaymentStatus::Paid ? 'text-emerald-700' : 'text-ink/45' }}">{{ $order->latestPayment->status->label() }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right font-bold text-munch">{{ Money::format($order->total) }}</td>
                            <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn-secondary !min-h-[36px] !px-3 !py-1 text-xs">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-admin.panel>
@endsection
