@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Organize the menu into clear sections')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<div class="grid gap-6 lg:grid-cols-3">
    <form method="POST" action="{{ route('admin.categories.store') }}" class="h-fit space-y-3 rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
        @csrf
        <h2 class="text-lg font-extrabold text-munch">Add category</h2>
        <div>
            <label class="label-field">Name</label>
            <input name="name" class="input-field" placeholder="e.g. Signature Sandwiches" required>
        </div>
        <div>
            <label class="label-field">Description</label>
            <textarea name="description" class="input-field" rows="2" placeholder="Short description"></textarea>
        </div>
        <div>
            <label class="label-field">Sort order</label>
            <input type="number" name="sort_order" class="input-field" value="0">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <button class="btn-primary w-full">Create</button>
    </form>

    <div class="space-y-3 lg:col-span-2">
        @forelse($categories as $category)
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
                @csrf @method('PUT')
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-extrabold text-munch">{{ $category->name }}</p>
                        <p class="mt-1 text-sm text-ink/45">{{ $category->products_count }} products · sort {{ $category->sort_order }}</p>
                    </div>
                    @if($category->is_active)
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">Active</span>
                    @else
                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-bold text-stone-600 ring-1 ring-stone-200">Hidden</span>
                    @endif
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-4">
                    <input name="name" value="{{ $category->name }}" class="input-field sm:col-span-2">
                    <input type="number" name="sort_order" value="{{ $category->sort_order }}" class="input-field">
                    <div class="flex items-center gap-3">
                        <label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Active</label>
                        <button class="btn-secondary !px-3 text-xs">Save</button>
                    </div>
                </div>
            </form>
        @empty
            <div class="rounded-[28px] border border-dashed border-munch/20 p-12 text-center text-ink/40">No categories yet.</div>
        @endforelse
    </div>
</div>
@endsection
