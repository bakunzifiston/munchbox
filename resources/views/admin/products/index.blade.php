@extends('layouts.admin')

@section('title', 'Products')
@section('heading', 'Menu / Products')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form><input type="search" name="q" value="{{ request('q') }}" placeholder="Search products" class="input-field max-w-xs"></form>
    <a href="{{ route('admin.products.create') }}" class="btn-primary">Add product</a>
</div>

<div class="overflow-hidden rounded-3xl border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-cream/80 text-xs uppercase text-ink/40">
            <tr>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Price</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-forest/5">
            @forelse($products as $product)
                <tr>
                    <td class="px-4 py-3 font-semibold text-munch">{{ $product->name }}</td>
                    <td class="px-4 py-3">{{ $product->category->name }}</td>
                    <td class="px-4 py-3">{{ $product->formattedPrice() }}</td>
                    <td class="px-4 py-3">{{ $product->is_active ? 'Active' : 'Hidden' }}</td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-box">Edit</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Delete product?')">
                            @csrf @method('DELETE')
                            <button class="font-semibold text-red-700">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-ink/40">No products yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
