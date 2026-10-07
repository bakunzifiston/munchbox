@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Today’s kitchen & delivery pulse')

@section('content')
@php
    $trend = function (?float $change) {
        if ($change === null) {
            return ['label' => 'vs yesterday', 'class' => 'text-ink/40', 'prefix' => ''];
        }
        if ($change > 0) {
            return ['label' => '+'.$change.'% vs yesterday', 'class' => 'text-emerald-700', 'prefix' => '↑'];
        }
        if ($change < 0) {
            return ['label' => $change.'% vs yesterday', 'class' => 'text-rose-700', 'prefix' => '↓'];
        }
        return ['label' => 'No change vs yesterday', 'class' => 'text-ink/40', 'prefix' => ''];
    };
    $ordersTrend = $trend($orders_today_change);
    $revenueTrend = $trend($revenue_today_change);
@endphp

{{-- Quick actions --}}
<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 rounded-full bg-munch px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-munch-deep">
        Create Order
    </a>
    <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold text-munch ring-1 ring-munch/10 transition hover:ring-box/30">Add Product</a>
    <a href="{{ route('admin.ingredients.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold text-munch ring-1 ring-munch/10 transition hover:ring-box/30">Add Ingredient</a>
    <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold text-munch ring-1 ring-munch/10 transition hover:ring-box/30">Manage Inventory</a>
    <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold text-munch ring-1 ring-munch/10 transition hover:ring-box/30">View Reports</a>
</div>

{{-- KPI cards --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-[24px] bg-white p-5 shadow-[0_18px_40px_-28px_rgba(16,48,24,0.45)] ring-1 ring-munch/5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-ink/40">Today’s orders</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-munch">{{ $orders_today }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sage text-munch">
                @include('layouts.partials.admin-icon', ['name' => 'orders', 'class' => 'h-5 w-5'])
            </span>
        </div>
        <p class="mt-3 text-xs text-ink/45">Orders placed today</p>
        <p class="mt-1 text-xs font-semibold {{ $ordersTrend['class'] }}">{{ $ordersTrend['prefix'] }} {{ $ordersTrend['label'] }}</p>
    </div>

    <div class="rounded-[24px] bg-white p-5 shadow-[0_18px_40px_-28px_rgba(16,48,24,0.45)] ring-1 ring-munch/5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-ink/40">Pending</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-munch">{{ $pending_orders }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </span>
        </div>
        <p class="mt-3 text-xs text-ink/45">Awaiting confirmation</p>
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="mt-1 inline-block text-xs font-bold text-box hover:underline">Review now →</a>
    </div>

    <div class="rounded-[24px] bg-white p-5 shadow-[0_18px_40px_-28px_rgba(16,48,24,0.45)] ring-1 ring-munch/5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-ink/40">Active</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-munch">{{ $active_orders }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                @include('layouts.partials.admin-icon', ['name' => 'delivery', 'class' => 'h-5 w-5'])
            </span>
        </div>
        <p class="mt-3 text-xs text-ink/45">In kitchen or delivery</p>
        <p class="mt-1 text-xs font-semibold text-ink/40">{{ $completed_today }} delivered today</p>
    </div>

    <div class="rounded-[24px] bg-munch p-5 text-white shadow-[0_18px_40px_-28px_rgba(16,48,24,0.55)]">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-white/50">Today’s revenue</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight">{{ $revenue_today_formatted }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-box text-white">
                @include('layouts.partials.admin-icon', ['name' => 'reports', 'class' => 'h-5 w-5'])
            </span>
        </div>
        <p class="mt-3 text-xs text-white/55">Excludes cancelled / rejected</p>
        <p class="mt-1 text-xs font-semibold {{ str_contains($revenueTrend['class'], 'emerald') ? 'text-emerald-300' : (str_contains($revenueTrend['class'], 'rose') ? 'text-rose-300' : 'text-white/45') }}">
            {{ $revenueTrend['prefix'] }} {{ $revenueTrend['label'] }}
        </p>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    {{-- Sales chart --}}
    <section class="rounded-[24px] bg-white p-5 shadow-[0_18px_40px_-28px_rgba(16,48,24,0.45)] ring-1 ring-munch/5 xl:col-span-2">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-munch">Sales overview</h2>
                <p class="text-sm text-ink/45">
                    {{ number_format($chart['total_orders']) }} orders · {{ \App\Support\Money::format($chart['total_revenue']) }}
                </p>
            </div>
            <div class="flex rounded-full bg-[#F6F4EF] p-1 text-xs font-bold">
                @foreach(['today' => 'Today', '7d' => '7 days', '30d' => '30 days'] as $key => $label)
                    <a href="{{ route('admin.dashboard', ['range' => $key]) }}"
                       class="rounded-full px-3 py-1.5 transition {{ $chart_range === $key ? 'bg-white text-munch shadow-sm' : 'text-ink/45 hover:text-munch' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @if($chart['total_orders'] === 0)
            <x-admin.empty-state
                title="No sales in this period"
                description="Once orders start coming in, revenue and order trends will appear here."
                :action-url="route('home')"
                action-label="Open storefront"
            >
                <x-slot:icon>
                    @include('layouts.partials.admin-icon', ['name' => 'reports', 'class' => 'h-6 w-6'])
                </x-slot:icon>
            </x-admin.empty-state>
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-ink/40">Revenue</p>
                    <div class="flex h-40 items-end gap-1.5 sm:gap-2">
                        @foreach($chart['revenue'] as $i => $value)
                            @php $height = max(8, ($value / $chart['max_revenue']) * 100); @endphp
                            <div class="group relative flex flex-1 flex-col items-center justify-end">
                                <div class="pointer-events-none absolute -top-7 hidden rounded-md bg-munch px-2 py-1 text-[10px] font-bold text-white group-hover:block">
                                    {{ number_format($value) }}
                                </div>
                                <div class="w-full rounded-t-lg bg-gradient-to-t from-munch to-box/80 transition group-hover:opacity-90" style="height: {{ $height }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-1.5 sm:gap-2">
                        @foreach($chart['labels'] as $label)
                            <span class="flex-1 truncate text-center text-[9px] font-medium text-ink/35 sm:text-[10px]">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-ink/40">Orders</p>
                    <div class="flex h-40 items-end gap-1.5 sm:gap-2">
                        @foreach($chart['orders'] as $i => $value)
                            @php $height = max(8, ($value / $chart['max_orders']) * 100); @endphp
                            <div class="group relative flex flex-1 flex-col items-center justify-end">
                                <div class="pointer-events-none absolute -top-7 hidden rounded-md bg-box px-2 py-1 text-[10px] font-bold text-white group-hover:block">
                                    {{ $value }}
                                </div>
                                <div class="w-full rounded-t-lg bg-box/80 transition group-hover:bg-box" style="height: {{ $height }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-1.5 sm:gap-2">
                        @foreach($chart['labels'] as $label)
                            <span class="flex-1 truncate text-center text-[9px] font-medium text-ink/35 sm:text-[10px]">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>

    {{-- Inventory alerts --}}
    <section class="rounded-[24px] bg-white p-5 shadow-[0_18px_40px_-28px_rgba(16,48,24,0.45)] ring-1 ring-munch/5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-munch">Inventory alerts</h2>
                <p class="text-sm text-ink/45">
                    @if($critical_stock_count > 0)
                        {{ $critical_stock_count }} critical · {{ $low_stock->count() }} low
                    @else
                        Stock health check
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.inventory.index') }}" class="text-xs font-bold text-box hover:underline">Manage</a>
        </div>

        @forelse($low_stock as $item)
            @php
                $qty = (float) $item->quantity_on_hand;
                $min = (float) $item->low_stock_threshold;
                $critical = $qty <= ($min / 2);
            @endphp
            <div class="mb-3 rounded-2xl border px-3 py-3 {{ $critical ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50' }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-bold {{ $critical ? 'text-rose-950' : 'text-amber-950' }}">{{ $item->ingredient->name }}</p>
                        <p class="mt-0.5 text-xs {{ $critical ? 'text-rose-800/70' : 'text-amber-800/70' }}">
                            Min {{ rtrim(rtrim(number_format($min, 2), '0'), '.') }} {{ $item->ingredient->unit }}
                        </p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $critical ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white' }}">
                        {{ $critical ? 'Critical' : 'Low' }}
                    </span>
                </div>
                <p class="mt-2 text-sm font-extrabold {{ $critical ? 'text-rose-950' : 'text-amber-950' }}">
                    {{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}
                    <span class="text-xs font-semibold opacity-70">{{ $item->ingredient->unit }}</span>
                </p>
            </div>
        @empty
            <x-admin.empty-state
                class="!py-8"
                title="Stock looks healthy"
                description="No ingredients are below their low-stock threshold right now."
                :action-url="route('admin.inventory.index')"
                action-label="View inventory"
            >
                <x-slot:icon>
                    @include('layouts.partials.admin-icon', ['name' => 'inventory', 'class' => 'h-6 w-6'])
                </x-slot:icon>
            </x-admin.empty-state>
        @endforelse
    </section>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    {{-- Recent orders --}}
    <section class="rounded-[24px] bg-white p-5 shadow-[0_18px_40px_-28px_rgba(16,48,24,0.45)] ring-1 ring-munch/5 xl:col-span-2">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-munch">Recent orders</h2>
                <p class="text-sm text-ink/45">Latest activity across the kitchen</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="rounded-full bg-sage px-3 py-1.5 text-xs font-bold text-munch transition hover:bg-munch hover:text-white">View all orders</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-munch/8 text-[11px] font-bold uppercase tracking-wider text-ink/35">
                        <th class="pb-3 pr-4">Order</th>
                        <th class="pb-3 pr-4">Customer</th>
                        <th class="pb-3 pr-4">Items</th>
                        <th class="pb-3 pr-4">Type</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3 pr-4 text-right">Amount</th>
                        <th class="pb-3 pl-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-munch/5">
                    @forelse($recent_orders as $order)
                        <tr class="align-middle">
                            <td class="py-3.5 pr-4">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-bold text-munch hover:text-box">{{ $order->order_number }}</a>
                                <p class="mt-0.5 text-[11px] text-ink/40">{{ $order->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="py-3.5 pr-4">
                                <p class="font-semibold text-ink">{{ $order->customer_name }}</p>
                                <p class="text-xs text-ink/40">{{ $order->customer_phone }}</p>
                            </td>
                            <td class="py-3.5 pr-4 text-ink/60">{{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}</td>
                            <td class="py-3.5 pr-4">
                                <span class="rounded-full bg-[#F6F4EF] px-2.5 py-1 text-[11px] font-bold text-munch">{{ $order->fulfillment_type->label() }}</span>
                            </td>
                            <td class="py-3.5 pr-4">
                                <x-admin.status-badge :status="$order->status" />
                            </td>
                            <td class="py-3.5 pr-4 text-right font-extrabold text-munch">{{ $order->formattedTotal() }}</td>
                            <td class="py-3.5 pl-4 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex rounded-full bg-munch px-3 py-1.5 text-[11px] font-bold text-white hover:bg-munch-deep">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-admin.empty-state
                                    title="No orders yet"
                                    description="When customers place sandwich orders, they’ll show up here for kitchen and delivery tracking."
                                    :action-url="route('home')"
                                    action-label="Go to storefront"
                                >
                                    <x-slot:icon>
                                        @include('layouts.partials.admin-icon', ['name' => 'orders', 'class' => 'h-6 w-6'])
                                    </x-slot:icon>
                                </x-admin.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Popular products --}}
    <section class="rounded-[24px] bg-white p-5 shadow-[0_18px_40px_-28px_rgba(16,48,24,0.45)] ring-1 ring-munch/5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-munch">Popular today</h2>
                <p class="text-sm text-ink/45">Product performance</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="text-xs font-bold text-box hover:underline">Menu</a>
        </div>

        <div class="space-y-3">
            @forelse($popular_products as $item)
                @php $pct = max(8, ((int) $item->total_qty / $popular_max_qty) * 100); @endphp
                <div class="rounded-2xl bg-[#F6F4EF]/80 p-3">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-cover bg-center ring-1 ring-munch/5"
                             style="background-image: url('{{ asset($item->image ?: 'images/sandwiches/classic.jpg') }}')"></div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-munch">{{ $item->product_name }}</p>
                            <p class="text-xs text-ink/45">{{ (int) $item->total_qty }} sold · {{ \App\Support\Money::format($item->revenue) }}</p>
                        </div>
                    </div>
                    <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-white">
                        <div class="h-full rounded-full bg-gradient-to-r from-munch to-box" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <x-admin.empty-state
                    class="!py-8"
                    title="No sales yet today"
                    description="Popular sandwiches will rank here once today’s orders start rolling in."
                    :action-url="route('admin.products.index')"
                    action-label="Manage products"
                >
                    <x-slot:icon>
                        @include('layouts.partials.admin-icon', ['name' => 'products', 'class' => 'h-6 w-6'])
                    </x-slot:icon>
                </x-admin.empty-state>
            @endforelse
        </div>
    </section>
</div>
@endsection
