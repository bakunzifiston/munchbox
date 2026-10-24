@extends('layouts.admin')

@section('title', 'Reports')
@section('heading', 'Reports')
@section('subheading', 'Sales, products, customers, delivery and stock · Kigali days')

@php
    use App\Enums\OrderStatus;
    use App\Support\Money;

    $query = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    $maxDaily = max(1, ...array_column($daily, 'booked'));
@endphp

@section('content')
<x-admin.panel>
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label for="from" class="label-field">From</label>
            <input id="from" type="date" name="from" value="{{ $query['from'] }}" class="input-field">
        </div>
        <div>
            <label for="to" class="label-field">To</label>
            <input id="to" type="date" name="to" value="{{ $query['to'] }}" class="input-field">
        </div>
        <button type="submit" class="btn-primary">Update</button>
        <div class="flex flex-wrap gap-1.5 text-xs font-bold">
            @foreach(['Today' => [0, 0], 'Last 7 days' => [6, 0], 'Last 30 days' => [29, 0]] as $label => [$back, $fwd])
                <a href="{{ route('admin.reports.index', ['from' => \App\Support\BusinessTime::day(-$back)->format('Y-m-d'), 'to' => \App\Support\BusinessTime::day()->format('Y-m-d')]) }}" class="rounded-full bg-[#F6F4EF] px-3 py-1.5 text-munch hover:bg-sage">{{ $label }}</a>
            @endforeach
        </div>
        <a href="{{ route('admin.reports.export', $query) }}" class="btn-secondary ml-auto">Export CSV</a>
    </form>
</x-admin.panel>

<div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
    <div class="stat-card"><p class="stat-label">Orders placed</p><p class="stat-value">{{ $summary['orders'] }}</p><p class="mt-1 text-xs text-ink/50">{{ $summary['cancelled'] }} cancelled or rejected</p></div>
    <div class="stat-card"><p class="stat-label">Booked sales</p><p class="stat-value">{{ Money::format($summary['booked']) }}</p><p class="mt-1 text-xs text-ink/50">{{ $summary['sales_orders'] }} orders, excl. cancelled</p></div>
    <div class="stat-card"><p class="stat-label">Collected</p><p class="stat-value">{{ Money::format($summary['collected']) }}</p><p class="mt-1 text-xs text-ink/50">Payments marked paid in period</p></div>
    <div class="stat-card"><p class="stat-label">Average order</p><p class="stat-value">{{ $summary['average'] !== null ? Money::format($summary['average']) : '—' }}</p><p class="mt-1 text-xs text-ink/50">Booked sales ÷ orders</p></div>
    <div class="stat-card"><p class="stat-label">Uncollected</p><p class="stat-value">{{ Money::format(max(0, $summary['booked'] - $summary['collected'])) }}</p><p class="mt-1 text-xs text-ink/50">Booked minus collected</p></div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-3">
    <x-admin.panel class="xl:col-span-2" title="Sales by day" :subtitle="$from->format('d M Y').' – '.$to->format('d M Y')">
        @if($summary['sales_orders'] === 0)
            <x-admin.empty-state title="No sales in this period" description="Pick a different date range to see results." class="!py-8" />
        @else
            <div class="-mx-5 max-h-96 overflow-auto">
                <table class="admin-table !min-w-[480px]">
                    <thead><tr><th>Day</th><th class="text-right">Orders</th><th class="text-right">Booked</th><th class="text-right">Collected</th><th class="w-1/3"></th></tr></thead>
                    <tbody>
                        @foreach(array_reverse($daily) as $day)
                            <tr>
                                <td class="whitespace-nowrap">{{ $day['label'] }}</td>
                                <td class="text-right">{{ $day['orders'] }}</td>
                                <td class="whitespace-nowrap text-right font-semibold">{{ Money::format($day['booked']) }}</td>
                                <td class="whitespace-nowrap text-right">{{ Money::format($day['collected']) }}</td>
                                <td><div class="h-2 rounded-full bg-munch" style="width: {{ round($day['booked'] / $maxDaily * 100) }}%"></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.panel>

    <x-admin.panel title="Orders by status">
        <ul class="space-y-2 text-sm">
            @foreach(OrderStatus::cases() as $status)
                @php $row = $byStatus[$status->value] ?? null; @endphp
                <li class="flex items-center justify-between gap-3">
                    <x-admin.status-badge :status="$status" />
                    <span class="font-semibold">{{ (int) ($row->total ?? 0) }} <span class="font-normal text-ink/45">· {{ Money::format($row->value ?? 0) }}</span></span>
                </li>
            @endforeach
        </ul>
    </x-admin.panel>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-2">
    <x-admin.panel title="Product performance" subtitle="Units and sales from order lines, excl. cancelled">
        @if($topProducts->isEmpty())
            <p class="py-6 text-center text-sm text-ink/50">No products sold in this period.</p>
        @else
            <div class="-mx-5 overflow-x-auto">
                <table class="admin-table !min-w-[420px]">
                    <thead><tr><th>Product</th><th class="text-right">Units</th><th class="text-right">Sales</th></tr></thead>
                    <tbody>
                        @foreach($topProducts as $row)
                            <tr><td class="font-semibold text-munch">{{ $row->product_name }}</td><td class="text-right">{{ $row->units }}</td><td class="whitespace-nowrap text-right">{{ Money::format($row->sales) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.panel>

    <x-admin.panel title="Top customers" subtitle="By booked sales in this period">
        @if($topCustomers->isEmpty())
            <p class="py-6 text-center text-sm text-ink/50">No customer orders in this period.</p>
        @else
            <div class="-mx-5 overflow-x-auto">
                <table class="admin-table !min-w-[420px]">
                    <thead><tr><th>Customer</th><th class="text-right">Orders</th><th class="text-right">Spent</th></tr></thead>
                    <tbody>
                        @foreach($topCustomers as $customer)
                            <tr>
                                <td><a href="{{ route('admin.customers.show', $customer->id) }}" class="font-semibold text-munch hover:text-box">{{ $customer->name }}</a><p class="text-xs text-ink/45">{{ $customer->phone }}</p></td>
                                <td class="text-right">{{ $customer->orders }}</td>
                                <td class="whitespace-nowrap text-right">{{ Money::format($customer->spent) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.panel>

    <x-admin.panel title="Delivery" subtitle="Delivery orders placed in this period (pickup excluded)">
        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div><dt class="stat-label">Orders</dt><dd class="text-lg font-extrabold text-munch">{{ $delivery['orders'] }}</dd></div>
            <div><dt class="stat-label">Delivered</dt><dd class="text-lg font-extrabold text-munch">{{ $delivery['delivered'] }}</dd></div>
            <div><dt class="stat-label">Cancelled</dt><dd class="text-lg font-extrabold text-munch">{{ $delivery['failed'] }}</dd></div>
            <div><dt class="stat-label">Avg. time</dt><dd class="text-lg font-extrabold text-munch">{{ $delivery['average_minutes'] !== null ? $delivery['average_minutes'].' min' : '—' }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-ink/50">Average time runs from order placed to marked delivered. Delivery fees booked: {{ Money::format($delivery['fees']) }}.</p>
    </x-admin.panel>

    <x-admin.panel title="Stock alerts" subtitle="Current stock, not limited to this period">
        @forelse($stockAlerts as $item)
            <div class="flex justify-between gap-3 border-b border-munch/5 py-2 text-sm last:border-0">
                <span class="font-semibold text-munch">{{ $item->ingredient?->name }}</span>
                <span class="text-ink/60">{{ $item->formattedQuantity() }} / min {{ $item->formattedThreshold() }}</span>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-ink/50">All stock is above its minimum.</p>
        @endforelse
    </x-admin.panel>
</div>
@endsection
