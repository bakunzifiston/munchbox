@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', \App\Support\BusinessTime::now()->format('l, d M Y').' · Kigali time')

@php
    use App\Support\Money;

    $points = $series['points'];
    $metricKey = $canSeeMoney ? 'booked' : 'orders';
    $maxValue = max(1, ...array_column($points, $metricKey));
    $hasData = array_sum(array_column($points, 'orders')) > 0;
    $labelEvery = count($points) > 14 ? 5 : (count($points) > 8 ? 3 : 1);
@endphp

@section('content')
{{-- Quick actions: each is shown only to people allowed to use it. --}}
<div class="mb-5 flex flex-wrap gap-2">
    @can('manage-orders')
        <a href="{{ route('admin.orders.create') }}" class="quick-action">@include('layouts.partials.admin-icon', ['name' => 'orders']) Create order</a>
    @endcan
    @can('manage-catalog')
        <a href="{{ route('admin.products.create') }}" class="quick-action">@include('layouts.partials.admin-icon', ['name' => 'products']) Add product</a>
        <a href="{{ route('admin.ingredients.index') }}#add-ingredient" class="quick-action">@include('layouts.partials.admin-icon', ['name' => 'ingredients']) Add ingredient</a>
    @endcan
    @can('manage-stock')
        <a href="{{ route('admin.inventory.index') }}" class="quick-action">@include('layouts.partials.admin-icon', ['name' => 'inventory']) Manage inventory</a>
    @endcan
    @can('view-financials')
        <a href="{{ route('admin.reports.index') }}" class="quick-action">@include('layouts.partials.admin-icon', ['name' => 'reports']) View reports</a>
    @endcan
    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="quick-action">Open storefront ↗</a>
</div>

{{-- Today --}}
<div class="grid gap-3 sm:grid-cols-2 {{ $canSeeMoney ? 'xl:grid-cols-5' : 'xl:grid-cols-4' }}">
    <div class="stat-card">
        <p class="stat-label">Orders today</p>
        <p class="stat-value">{{ $today['orders'] }}</p>
        <x-admin.change :change="$ordersChange" class="mt-1" />
    </div>

    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="stat-card group hover:ring-amber-300">
        <p class="stat-label">Pending</p>
        <p class="stat-value {{ $pendingCount > 0 ? 'text-amber-700' : '' }}">{{ $pendingCount }}</p>
        <p class="mt-1 text-xs text-ink/50">Waiting to be confirmed · <span class="font-bold text-box group-hover:underline">Review</span></p>
    </a>

    <a href="{{ route('admin.orders.index', ['status' => 'in_progress']) }}" class="stat-card group">
        <p class="stat-label">In progress</p>
        <p class="stat-value">{{ $inProgressCount }}</p>
        <p class="mt-1 text-xs text-ink/50">Confirmed → out for delivery · {{ $deliveredToday }} completed today</p>
    </a>

    @if($canSeeMoney)
        <div class="stat-card">
            <p class="stat-label" title="Totals of today’s orders, excluding cancelled and rejected">Booked sales today</p>
            <p class="stat-value">{{ Money::format($today['booked']) }}</p>
            <x-admin.change :change="$bookedChange" money class="mt-1" />
        </div>
        <div class="stat-card">
            <p class="stat-label" title="Payments marked as paid today">Collected today</p>
            <p class="stat-value">{{ Money::format($today['collected']) }}</p>
            <x-admin.change :change="$collectedChange" money class="mt-1" />
        </div>
    @else
        <a href="{{ route('admin.inventory.index') }}" class="stat-card">
            <p class="stat-label">Stock alerts</p>
            <p class="stat-value {{ $stockAlertCount > 0 ? 'text-rose-700' : '' }}">{{ $stockAlertCount }}</p>
            <p class="mt-1 text-xs text-ink/50">At or below minimum</p>
        </a>
    @endif
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    {{-- Sales overview --}}
    <x-admin.panel class="xl:col-span-2" :title="$canSeeMoney ? 'Sales overview' : 'Order volume'" :subtitle="$series['from']->format('d M').($series['range'] === 'today' ? '' : ' – '.$series['to']->format('d M')).' · cancelled and rejected orders excluded'">
        <x-slot:actions>
            <nav class="flex rounded-full bg-[#F6F4EF] p-1 text-xs font-bold" aria-label="Period">
                @foreach($ranges as $key => $label)
                    <a href="{{ route('admin.dashboard', ['range' => $key]) }}" class="rounded-full px-3 py-1.5 transition {{ $series['range'] === $key ? 'bg-white text-munch shadow-sm' : 'text-ink/50 hover:text-munch' }}" @if($series['range'] === $key) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </x-slot:actions>

        <dl class="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div><dt class="stat-label">Orders</dt><dd class="text-lg font-extrabold text-munch">{{ $seriesSummary['sales_orders'] }}</dd></div>
            @if($canSeeMoney)
                <div><dt class="stat-label">Booked</dt><dd class="text-lg font-extrabold text-munch">{{ Money::format($seriesSummary['booked']) }}</dd></div>
                <div><dt class="stat-label">Collected</dt><dd class="text-lg font-extrabold text-munch">{{ Money::format($seriesSummary['collected']) }}</dd></div>
                <div><dt class="stat-label">Average order</dt><dd class="text-lg font-extrabold text-munch">{{ $seriesSummary['average'] !== null ? Money::format($seriesSummary['average']) : '—' }}</dd></div>
            @endif
        </dl>

        @if(! $hasData)
            <x-admin.empty-state title="No orders in this period" description="Orders will appear here as soon as customers start ordering." class="!py-10" />
        @else
            <div class="flex h-48 items-end gap-[3px] border-b border-munch/10" role="img" aria-label="{{ $canSeeMoney ? 'Booked sales' : 'Orders' }} per {{ $series['range'] === 'today' ? 'hour' : 'day' }}">
                @foreach($points as $point)
                    @php $height = $point[$metricKey] > 0 ? max(3, round($point[$metricKey] / $maxValue * 100)) : 0; @endphp
                    <div class="group relative flex h-full flex-1 items-end">
                        <div class="w-full rounded-t {{ $point[$metricKey] > 0 ? 'bg-munch group-hover:bg-box' : '' }}" style="height: {{ $height }}%"></div>
                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-munch-deep px-2.5 py-1.5 text-[11px] text-white shadow-lg group-hover:block">
                            <span class="font-bold">{{ $point['label'] }}</span> · {{ $point['orders'] }} {{ Str::plural('order', $point['orders']) }}
                            @if($canSeeMoney)<br>{{ Money::format($point['booked']) }} booked · {{ Money::format($point['collected']) }} collected @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1.5 flex gap-[3px] text-[10px] text-ink/45">
                @foreach($points as $i => $point)
                    <span class="flex-1 truncate text-center">{{ $i % $labelEvery === 0 ? $point['label'] : '' }}</span>
                @endforeach
            </div>
        @endif
    </x-admin.panel>

    {{-- Stock alerts --}}
    <x-admin.panel title="Stock alerts" :subtitle="$stockAlertCount ? $stockAlertCount.' at or below minimum' : 'All stock above minimum'">
        @can('manage-stock')
            <x-slot:actions><a href="{{ route('admin.inventory.index') }}" class="text-xs font-bold text-box hover:underline">Manage</a></x-slot:actions>
        @endcan
        @forelse($stockAlerts as $item)
            @php $out = (float) $item->quantity_on_hand <= 0; @endphp
            <div class="flex items-center justify-between gap-3 border-b border-munch/5 py-2.5 last:border-0">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-munch">{{ $item->ingredient?->name }}</p>
                    <p class="text-xs text-ink/50">{{ $item->formattedQuantity() }} left · minimum {{ $item->formattedThreshold() }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $out ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800' }}">{{ $out ? 'Out' : 'Low' }}</span>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-ink/50">Nothing needs restocking.</p>
        @endforelse
    </x-admin.panel>
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    {{-- Recent orders --}}
    <x-admin.panel class="xl:col-span-2" title="Recent orders">
        <x-slot:actions><a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-box hover:underline">View all orders</a></x-slot:actions>
        @if($recentOrders->isEmpty())
            <x-admin.empty-state title="No orders yet" description="New orders from the storefront or entered by staff show up here." />
        @else
            <div class="-mx-5 overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr><th>Order</th><th>Customer</th><th>Items</th><th>Type</th><th>Status</th><th class="text-right">Total</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            <tr>
                                <td>
                                    <p class="font-mono text-xs font-bold text-munch">{{ $order->order_number }}</p>
                                    <p class="text-xs text-ink/45" title="{{ $order->created_at->format('d M Y, H:i') }}">{{ $order->created_at->isToday() ? $order->created_at->format('H:i').' · '.$order->created_at->diffForHumans() : $order->created_at->format('d M, H:i') }}</p>
                                </td>
                                <td>
                                    <p class="font-semibold text-munch">{{ $order->customer_name }}</p>
                                    <p class="text-xs text-ink/45">{{ $order->customer_phone }}</p>
                                </td>
                                <td class="whitespace-nowrap text-ink/70">{{ (int) $order->items_sum_quantity }} {{ Str::plural('unit', (int) $order->items_sum_quantity) }}</td>
                                <td class="text-ink/70">{{ $order->fulfillment_type->label() }}</td>
                                <td><x-admin.status-badge :status="$order->status" /></td>
                                <td class="whitespace-nowrap text-right font-bold text-munch">{{ Money::format($order->total) }}</td>
                                <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-bold text-box hover:underline">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.panel>

    {{-- Popular today --}}
    <x-admin.panel title="Popular today" subtitle="Units sold from today’s orders">
        @can('manage-catalog')
            <x-slot:actions><a href="{{ route('admin.products.index') }}" class="text-xs font-bold text-box hover:underline">Manage products</a></x-slot:actions>
        @endcan
        @forelse($popular as $row)
            <div class="flex items-center justify-between gap-3 border-b border-munch/5 py-2.5 last:border-0">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-munch">{{ $row->product_name }}</p>
                    <p class="text-xs text-ink/50">
                        {{ $row->units }} sold
                        @if($canSeeMoney) · {{ Money::format($row->sales) }} @endif
                    </p>
                </div>
                @php $delta = $row->units - (int) $row->previous_units; @endphp
                <span class="shrink-0 text-xs font-semibold {{ $delta > 0 ? 'text-emerald-700' : ($delta < 0 ? 'text-rose-700' : 'text-ink/40') }}" title="Compared with yesterday ({{ (int) $row->previous_units }})">
                    {{ $delta > 0 ? '+'.$delta : ($delta < 0 ? '−'.abs($delta) : '±0') }} vs yesterday
                </span>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-ink/50">No sales yet today.</p>
        @endforelse
    </x-admin.panel>
</div>
@endsection
