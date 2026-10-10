@extends('layouts.admin')

@section('title', 'Reports')
@section('heading', 'Reports')
@section('subheading', 'Revenue and product performance')

@section('content')
<form class="mb-6 flex flex-wrap gap-3">
    <input type="date" name="from" value="{{ $from->toDateString() }}" class="input-field max-w-[180px]">
    <input type="date" name="to" value="{{ $to->toDateString() }}" class="input-field max-w-[180px]">
    <button class="btn-primary">Apply</button>
</form>

<x-admin.kpi-grid :items="$kpis" />

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-extrabold text-munch">Daily revenue</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse($byDay as $day)
                <li class="flex justify-between rounded-2xl bg-[#F7F5F1] px-3 py-2.5">
                    <span>{{ \Carbon\Carbon::parse($day->day)->format('d M') }} · {{ $day->orders }} orders</span>
                    <span class="font-bold text-munch">{{ number_format($day->revenue, 0) }} RWF</span>
                </li>
            @empty
                <li class="text-ink/40">No data in range.</li>
            @endforelse
        </ul>
    </section>
    <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-extrabold text-munch">Top products</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse($topProducts as $product)
                <li class="flex justify-between rounded-2xl bg-[#F7F5F1] px-3 py-2.5">
                    <span>{{ $product->product_name }} · {{ $product->qty }} sold</span>
                    <span class="font-bold text-munch">{{ number_format($product->revenue, 0) }} RWF</span>
                </li>
            @empty
                <li class="text-ink/40">No product sales in range.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
