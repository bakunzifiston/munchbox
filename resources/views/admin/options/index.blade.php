@extends('layouts.admin')

@section('title', 'Menu Options')
@section('heading', 'Menu Options')
@section('subheading', 'Serving style, bread, proteins, toppings & sauces')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<p class="mb-6 text-sm text-ink/55">
    These are the customization choices customers see when building a sandwich.
    Attach groups to products under
    <a href="{{ route('admin.products.index') }}" class="font-bold text-box hover:underline">Menu & Products</a>.
</p>

<div class="grid gap-6 xl:grid-cols-3">
    <form method="POST" action="{{ route('admin.options.store') }}" class="h-fit space-y-3 rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
        @csrf
        <h2 class="text-lg font-extrabold text-munch">Add option group</h2>
        <div>
            <label class="label-field">Name</label>
            <input name="name" class="input-field" placeholder="e.g. Serving Style" required value="{{ old('name') }}">
        </div>
        <div>
            <label class="label-field">Type</label>
            <select name="type" class="input-field" required>
                @foreach($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label-field">Selection</label>
            <select name="selection_type" class="input-field" required>
                @foreach($selectionTypes as $selection)
                    <option value="{{ $selection->value }}" @selected(old('selection_type', 'single') === $selection->value)>
                        {{ $selection === \App\Enums\SelectionType::Single ? 'Choose one' : 'Choose multiple' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label-field">Min</label>
                <input type="number" name="min_selections" class="input-field" value="{{ old('min_selections', 0) }}" min="0" max="50" required>
            </div>
            <div>
                <label class="label-field">Max</label>
                <input type="number" name="max_selections" class="input-field" value="{{ old('max_selections', 1) }}" min="1" max="50" required>
            </div>
        </div>
        <div>
            <label class="label-field">Sort order</label>
            <input type="number" name="sort_order" class="input-field" value="{{ old('sort_order', $groups->count()) }}" min="0">
        </div>
        <div class="flex flex-wrap gap-4 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_required" value="1" @checked(old('is_required'))> Required</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active</label>
        </div>
        <button class="btn-primary w-full">Create group</button>
    </form>

    <div class="space-y-3 xl:col-span-2">
        @forelse($groups as $group)
            <a href="{{ route('admin.options.show', $group) }}"
               class="block rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm transition hover:border-box/40 hover:shadow-md">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-extrabold text-munch">{{ $group->name }}</h3>
                            @unless($group->is_active)
                                <span class="rounded-full bg-ink/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/50">Inactive</span>
                            @endunless
                            @if($group->is_required)
                                <span class="rounded-full bg-box/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-box">Required</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-ink/50">
                            {{ $group->type->label() }} ·
                            {{ $group->selection_type === \App\Enums\SelectionType::Single ? 'Choose one' : 'Choose up to '.$group->max_selections }}
                        </p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-extrabold text-munch">{{ $group->options_count }} choices</p>
                        <p class="text-xs text-ink/40">{{ $group->products_count }} products</p>
                    </div>
                </div>
                <p class="mt-3 text-xs font-bold text-box">Manage choices →</p>
            </a>
        @empty
            <x-admin.empty-state
                title="No option groups yet"
                description="Create groups like Serving Style, Bread, Proteins, Toppings, and Sauces, then add choices inside each group."
            >
                <x-slot:icon>
                    @include('layouts.partials.admin-icon', ['name' => 'options', 'class' => 'h-6 w-6'])
                </x-slot:icon>
            </x-admin.empty-state>
        @endforelse
    </div>
</div>
@endsection
