@extends('layouts.admin')

@section('title', 'Inventory')
@section('heading', 'Inventory')
@section('subheading', 'Stock on hand, restocks, and usage')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

@if($lowStock->isNotEmpty())
    <div class="mb-4 rounded-[24px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        <strong>{{ $lowStock->count() }}</strong> item(s) below threshold:
        {{ $lowStock->pluck('ingredient.name')->join(', ') }}
    </div>
@endif

<div class="space-y-4">
    @forelse($items as $item)
        <div class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-extrabold text-munch">{{ $item->ingredient->name }}</h2>
                    <p class="mt-1 text-sm text-ink/55">
                        On hand: <strong class="text-munch">{{ rtrim(rtrim(number_format((float) $item->quantity_on_hand, 2), '0'), '.') }}</strong> {{ $item->ingredient->unit }}
                        · Threshold {{ rtrim(rtrim(number_format((float) $item->low_stock_threshold, 2), '0'), '.') }}
                        @if($item->isLowStock())
                            <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900">Low</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('admin.inventory.movements', $item) }}" class="btn-ghost text-sm">Movements</a>
            </div>
            <form method="POST" action="{{ route('admin.inventory.adjust', $item) }}" class="mt-4 grid gap-3 sm:grid-cols-5">
                @csrf
                <select name="action" class="input-field">
                    <option value="receive">Receive</option>
                    <option value="use">Use</option>
                    <option value="waste">Waste</option>
                    <option value="adjustment">Adjustment (+/-)</option>
                </select>
                <input type="number" step="0.01" name="quantity" class="input-field" placeholder="Qty" required>
                <input type="number" step="0.01" name="signed_quantity" class="input-field" placeholder="Signed qty (adj)">
                <input name="notes" class="input-field" placeholder="Notes">
                <button class="btn-primary">Apply</button>
            </form>
        </div>
    @empty
        <div class="rounded-[28px] border border-dashed border-munch/20 p-12 text-center text-ink/40">No inventory items yet.</div>
    @endforelse
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
