@extends('layouts.admin')

@section('title', 'Products')
@section('heading', 'Menu & Products')
@section('subheading', 'What customers see and order on the storefront')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form class="flex-1">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products" class="input-field max-w-xs">
    </form>
    <a href="{{ route('admin.products.create') }}" class="btn-primary">Add product</a>
</div>

<div class="overflow-hidden rounded-[28px] border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-[#FAF8F4] text-[11px] font-bold uppercase tracking-wider text-ink/40">
            <tr>
                <th class="px-5 py-3">Product</th>
                <th class="px-5 py-3">Category</th>
                <th class="px-5 py-3">Price</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-munch/8">
            @forelse($products as $product)
                <tr class="transition hover:bg-[#FAF8F4]/70">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-14 w-14 shrink-0 rounded-2xl bg-cover bg-center ring-1 ring-munch/10"
                                style="background-image: url('{{ asset($product->image ?: 'images/sandwiches/classic.jpg') }}')"
                            ></div>
                            <div>
                                <p class="font-bold text-munch">{{ $product->name }}</p>
                                <p class="text-xs text-ink/45">
                                    {{ $product->is_customizable ? 'Customizable' : 'Fixed item' }}
                                    @if($product->is_featured) · Featured @endif
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-ink/70">{{ $product->category?->name }}</td>
                    <td class="px-5 py-4 font-semibold text-munch">{{ $product->formattedPrice() }}</td>
                    <td class="px-5 py-4">
                        @if($product->is_active)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">Active</span>
                        @else
                            <span class="rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-bold text-stone-600 ring-1 ring-stone-200">Hidden</span>
                        @endif
                    </td>
                    <td class="space-x-3 px-5 py-4 text-right">
                        <a href="{{ route('admin.products.edit', $product) }}" class="font-bold text-box hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Delete product?')">
                            @csrf @method('DELETE')
                            <button class="font-bold text-rose-700 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-14 text-center text-ink/40">No products yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
