@extends('layouts.admin')

@section('title', $group->name)
@section('heading', $group->name)
@section('subheading', 'Add, edit, or remove customer choices')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.options.index') }}" class="text-sm font-bold text-box hover:underline">← All option groups</a>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    {{-- Group settings --}}
    <div class="space-y-4">
        <form method="POST" action="{{ route('admin.options.update', $group) }}" class="space-y-3 rounded-[24px] border border-munch/10 bg-white p-5 shadow-sm">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-extrabold text-munch">Group settings</h2>
            <div>
                <label class="label-field">Name</label>
                <input name="name" class="input-field" value="{{ old('name', $group->name) }}" required>
            </div>
            <div>
                <label class="label-field">Type</label>
                <select name="type" class="input-field" required>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $group->type->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-field">Selection</label>
                <select name="selection_type" class="input-field" required>
                    @foreach($selectionTypes as $selection)
                        <option value="{{ $selection->value }}" @selected(old('selection_type', $group->selection_type->value) === $selection->value)>
                            {{ $selection === \App\Enums\SelectionType::Single ? 'Choose one' : 'Choose multiple' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label-field">Min selections</label>
                    <input type="number" name="min_selections" class="input-field" value="{{ old('min_selections', $group->min_selections) }}" min="0" max="50" required>
                </div>
                <div>
                    <label class="label-field">Max selections</label>
                    <input type="number" name="max_selections" class="input-field" value="{{ old('max_selections', $group->max_selections) }}" min="1" max="50" required>
                </div>
            </div>
            <div>
                <label class="label-field">Sort order</label>
                <input type="number" name="sort_order" class="input-field" value="{{ old('sort_order', $group->sort_order) }}" min="0">
            </div>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_required" value="1" @checked(old('is_required', $group->is_required))> Required</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active))> Active</label>
            </div>
            <button class="btn-primary w-full">Save group</button>
        </form>

        <form method="POST" action="{{ route('admin.options.destroy', $group) }}" onsubmit="return confirm('Delete this entire group and all its choices?')">
            @csrf
            @method('DELETE')
            <button class="w-full rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-100">
                Delete group
            </button>
        </form>

        <form method="POST" action="{{ route('admin.options.choices.store', $group) }}" class="space-y-3 rounded-[24px] border border-munch/10 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="text-lg font-extrabold text-munch">Add choice</h2>
            <div>
                <label class="label-field">Name</label>
                <input name="name" class="input-field" placeholder="e.g. Fresh, Ham, Mayo" required>
            </div>
            <div>
                <label class="label-field">Price adjustment (RWF)</label>
                <input type="number" name="price_adjustment" class="input-field" value="0" step="100">
                <p class="mt-1 text-xs text-ink/40">Use 0 for Included</p>
            </div>
            <div>
                <label class="label-field">Link ingredient (optional)</label>
                <select name="ingredient_id" class="input-field">
                    <option value="">— None —</option>
                    @foreach($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">{{ $ingredient->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-field">Sort order</label>
                <input type="number" name="sort_order" class="input-field" value="{{ $group->options->count() }}" min="0">
            </div>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1"> Default</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>
            <button class="btn-primary w-full">Add choice</button>
        </form>
    </div>

    {{-- Choices list --}}
    <div class="space-y-3 xl:col-span-2">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-extrabold text-munch">Choices ({{ $group->options->count() }})</h2>
        </div>

        @forelse($group->options as $choice)
            <div class="rounded-[24px] border border-munch/10 bg-white p-4 shadow-sm {{ $choice->is_active ? '' : 'opacity-60' }}">
                <form method="POST" action="{{ route('admin.options.choices.update', [$group, $choice]) }}">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-3 sm:grid-cols-12 sm:items-end">
                        <div class="sm:col-span-4">
                            <label class="label-field">Name</label>
                            <input name="name" class="input-field" value="{{ $choice->name }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label-field">Price (RWF)</label>
                            <input type="number" name="price_adjustment" class="input-field" value="{{ (float) $choice->price_adjustment }}" step="100">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="label-field">Ingredient</label>
                            <select name="ingredient_id" class="input-field">
                                <option value="">— None —</option>
                                @foreach($ingredients as $ingredient)
                                    <option value="{{ $ingredient->id }}" @selected($choice->ingredient_id == $ingredient->id)>{{ $ingredient->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-1">
                            <label class="label-field">Sort</label>
                            <input type="number" name="sort_order" class="input-field" value="{{ $choice->sort_order }}" min="0">
                        </div>
                        <div class="flex flex-wrap items-center gap-3 sm:col-span-2 sm:justify-end sm:pb-1">
                            <label class="text-xs font-semibold"><input type="checkbox" name="is_default" value="1" @checked($choice->is_default)> Default</label>
                            <label class="text-xs font-semibold"><input type="checkbox" name="is_active" value="1" @checked($choice->is_active)> Active</label>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-ink/40">
                            Shows as <span class="font-semibold text-munch">{{ $choice->formattedPriceAdjustment() }}</span> on the menu
                        </p>
                        <button class="btn-secondary !px-3 text-xs">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.options.choices.destroy', [$group, $choice]) }}" class="mt-2 flex justify-end"
                      onsubmit="return confirm('Remove {{ addslashes($choice->name) }}?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">
                        Remove
                    </button>
                </form>
            </div>
        @empty
            <x-admin.empty-state
                title="No choices in this group"
                description="Add choices on the left — for example Fresh / Toasted, or Ham / Cheese."
            >
                <x-slot:icon>
                    @include('layouts.partials.admin-icon', ['name' => 'options', 'class' => 'h-6 w-6'])
                </x-slot:icon>
            </x-admin.empty-state>
        @endforelse
    </div>
</div>
@endsection
