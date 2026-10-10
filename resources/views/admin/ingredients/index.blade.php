@extends('layouts.admin')

@section('title', 'Ingredients')
@section('heading', 'Ingredients')
@section('subheading', 'Catalog items used in recipes and inventory')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<p class="mb-6 text-sm text-ink/55">
    Stock quantity is adjusted under
    <a href="{{ route('admin.inventory.index') }}" class="font-bold text-box hover:underline">Inventory</a>.
</p>

<div class="grid gap-6 xl:grid-cols-3">
    <form method="POST" action="{{ route('admin.ingredients.store') }}" class="h-fit space-y-3 rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
        @csrf
        <h2 class="text-lg font-extrabold text-munch">Add ingredient</h2>
        <div>
            <label class="label-field">Name</label>
            <input name="name" class="input-field" placeholder="e.g. Ham" required value="{{ old('name') }}">
        </div>
        <div>
            <label class="label-field">SKU</label>
            <input name="sku" class="input-field" placeholder="Optional" value="{{ old('sku') }}">
        </div>
        <div>
            <label class="label-field">Unit</label>
            <input name="unit" class="input-field" placeholder="pcs, g, ml" required value="{{ old('unit', 'pcs') }}">
        </div>
        <div>
            <label class="label-field">Opening stock</label>
            <input type="number" step="0.01" name="quantity_on_hand" class="input-field" value="{{ old('quantity_on_hand', 0) }}">
        </div>
        <div>
            <label class="label-field">Low stock threshold</label>
            <input type="number" step="0.01" name="low_stock_threshold" class="input-field" value="{{ old('low_stock_threshold', 10) }}">
        </div>
        <div>
            <label class="label-field">Unit cost (RWF)</label>
            <input type="number" step="0.01" name="unit_cost" class="input-field" value="{{ old('unit_cost', 0) }}">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active</label>
        <button class="btn-primary w-full">Create</button>
    </form>

    <div class="space-y-3 xl:col-span-2">
        @forelse($ingredients as $ingredient)
            @php $low = $ingredient->inventoryItem?->isLowStock(); @endphp
            <div class="rounded-[28px] border bg-white p-5 shadow-sm {{ $low ? 'border-amber-200' : 'border-munch/10' }} {{ $ingredient->is_active ? '' : 'opacity-60' }}">
                <form method="POST" action="{{ route('admin.ingredients.update', $ingredient) }}">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-3 sm:grid-cols-12 sm:items-end">
                        <div class="sm:col-span-4">
                            <label class="label-field">Name</label>
                            <input name="name" class="input-field" value="{{ $ingredient->name }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label-field">SKU</label>
                            <input name="sku" class="input-field" value="{{ $ingredient->sku }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label-field">Unit</label>
                            <input name="unit" class="input-field" value="{{ $ingredient->unit }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label-field">Threshold</label>
                            <input type="number" step="0.01" name="low_stock_threshold" class="input-field"
                                   value="{{ $ingredient->inventoryItem?->low_stock_threshold ?? 10 }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label-field">Unit cost</label>
                            <input type="number" step="0.01" name="unit_cost" class="input-field"
                                   value="{{ $ingredient->inventoryItem?->unit_cost ?? 0 }}">
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <span class="rounded-full px-2.5 py-1 font-bold {{ $low ? 'bg-amber-100 text-amber-800' : 'bg-[#F6F4EF] text-munch' }}">
                                Stock: {{ rtrim(rtrim(number_format((float) ($ingredient->inventoryItem?->quantity_on_hand ?? 0), 2), '0'), '.') }} {{ $ingredient->unit }}
                            </span>
                            @if($low)
                                <span class="rounded-full bg-amber-500 px-2.5 py-1 font-bold uppercase tracking-wide text-white">Low</span>
                            @endif
                            <label class="font-semibold text-ink/70"><input type="checkbox" name="is_active" value="1" @checked($ingredient->is_active)> Active</label>
                        </div>
                        <button class="btn-secondary !px-3 text-xs">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.ingredients.destroy', $ingredient) }}" class="mt-2 flex justify-end"
                      onsubmit="return confirm('Delete {{ addslashes($ingredient->name) }}? This also removes its inventory record.')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">
                        Delete
                    </button>
                </form>
            </div>
        @empty
            <x-admin.empty-state
                title="No ingredients yet"
                description="Add ingredients like Ham, Lettuce, or Mayo so they can be linked to menu options and tracked in inventory."
            >
                <x-slot:icon>
                    @include('layouts.partials.admin-icon', ['name' => 'ingredients', 'class' => 'h-6 w-6'])
                </x-slot:icon>
            </x-admin.empty-state>
        @endforelse

        <div class="pt-2">{{ $ingredients->links() }}</div>
    </div>
</div>
@endsection
