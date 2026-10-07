@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <form method="POST" action="{{ route('admin.categories.store') }}" class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm space-y-3 h-fit">
        @csrf
        <h2 class="font-display text-lg font-bold text-munch">Add category</h2>
        <input name="name" class="input-field" placeholder="Name" required>
        <textarea name="description" class="input-field" rows="2" placeholder="Description"></textarea>
        <input type="number" name="sort_order" class="input-field" placeholder="Sort order" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <button class="btn-primary w-full">Create</button>
    </form>

    <div class="lg:col-span-2 space-y-3">
        @forelse($categories as $category)
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="rounded-3xl border border-munch/10 bg-white p-4 shadow-sm">
                @csrf @method('PUT')
                <div class="grid gap-3 sm:grid-cols-4">
                    <input name="name" value="{{ $category->name }}" class="input-field sm:col-span-2">
                    <input type="number" name="sort_order" value="{{ $category->sort_order }}" class="input-field">
                    <div class="flex items-center gap-3">
                        <label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Active</label>
                        <button class="btn-secondary !px-3 text-xs">Save</button>
                    </div>
                </div>
                <p class="mt-2 text-xs text-ink/45">{{ $category->products_count }} products</p>
            </form>
        @empty
            <div class="rounded-3xl border border-dashed border-munch/20 p-10 text-center text-ink/40">No categories yet.</div>
        @endforelse
    </div>
</div>
@endsection
