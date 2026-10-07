@extends('layouts.admin')

@section('title', 'Inventory')
@section('heading', 'Inventory')

@section('content')
@if($lowStock->isNotEmpty())
    <div class="mb-4 rounded-3xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
        <strong>{{ $lowStock->count() }}</strong> ingredient(s) below threshold:
        {{ $lowStock->pluck('ingredient.name')->join(', ') }}
    </div>
@endif

<div class="space-y-4">
    @foreach($items as $item)
        <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-bold text-munch">{{ $item->ingredient->name }}</h2>
                    <p class="text-sm text-ink/55">On hand: <strong>{{ $item->quantity_on_hand }}</strong> {{ $item->ingredient->unit }}
                        · Threshold {{ $item->low_stock_threshold }}
                        @if($item->isLowStock()) <span class="badge bg-amber-200 text-amber-950 ml-2">Low</span> @endif
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
    @endforeach
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
