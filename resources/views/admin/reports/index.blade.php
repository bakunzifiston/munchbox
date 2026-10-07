@extends('layouts.admin')

@section('title', 'Reports')
@section('heading', 'Reports')

@section('content')
<form class="mb-6 flex flex-wrap gap-3">
    <input type="date" name="from" value="{{ $from->toDateString() }}" class="input-field max-w-[180px]">
    <input type="date" name="to" value="{{ $to->toDateString() }}" class="input-field max-w-[180px]">
    <button class="btn-primary">Apply</button>
</form>

<div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-ink/40">Revenue</p>
        <p class="mt-2 font-display text-3xl font-bold text-munch">{{ $revenueFormatted }}</p>
    </div>
    <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-ink/40">Orders</p>
        <p class="mt-2 font-display text-3xl font-bold text-munch">{{ $orderCount }}</p>
    </div>
    <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-ink/40">Avg order</p>
        <p class="mt-2 font-display text-3xl font-bold text-munch">{{ $avgOrder }}</p>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
        <h2 class="font-display text-lg font-bold text-munch">Daily revenue</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse($byDay as $day)
                <li class="flex justify-between">
                    <span>{{ \Carbon\Carbon::parse($day->day)->format('d M') }} · {{ $day->orders }} orders</span>
                    <span class="font-semibold">{{ number_format($day->revenue, 0) }} RWF</span>
                </li>
            @empty
                <li class="text-ink/40">No data in range.</li>
            @endforelse
        </ul>
    </div>
    <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
        <h2 class="font-display text-lg font-bold text-munch">Top products</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse($topProducts as $product)
                <li class="flex justify-between">
                    <span>{{ $product->product_name }} · {{ $product->qty }} sold</span>
                    <span class="font-semibold">{{ number_format($product->revenue, 0) }} RWF</span>
                </li>
            @empty
                <li class="text-ink/40">No product sales in range.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
