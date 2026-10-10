@extends('layouts.customer')

@section('title', 'Cart')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-4xl font-bold text-munch">Your cart</h1>
            @if($items->isNotEmpty())
                <p class="mt-2 text-ink/60">
                    {{ $count }} {{ Str::plural('item', $count) }} · add as many sandwiches as you like
                </p>
            @endif
        </div>
        @if($items->isNotEmpty())
            <a href="{{ route('menu.index') }}" class="btn-secondary inline-flex items-center justify-center gap-2">
                <span aria-hidden="true">+</span> Add another item
            </a>
        @endif
    </div>

    @if($items->isEmpty())
        <div class="mt-10 card-surface p-12 text-center">
            <p class="font-display text-2xl text-munch">Cart is empty</p>
            <p class="mt-2 text-ink/60">Browse the menu and build your sandwich.</p>
            <a href="{{ route('menu.index') }}" class="btn-primary mt-6">Browse menu</a>
        </div>
    @else
        <div class="mt-8 space-y-4">
            @foreach($items as $item)
                @php
                    $groupedOptions = collect($item['options'] ?? [])->groupBy('group');
                @endphp
                <div class="card-surface p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 flex-1 gap-4">
                            <a href="{{ route('menu.show', $item['slug']) }}" class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-cover bg-center sm:h-24 sm:w-24" style="background-image: url('{{ asset($item['image'] ?: 'images/sandwiches/classic.jpg') }}')"></a>
                            <div class="min-w-0">
                                <a href="{{ route('menu.show', $item['slug']) }}" class="font-display text-xl font-semibold text-munch hover:text-box">
                                    {{ $item['name'] }}
                                </a>
                                @if($groupedOptions->isNotEmpty())
                                    <dl class="mt-2 space-y-1 text-sm text-ink/60">
                                        @foreach($groupedOptions as $group => $options)
                                            <div>
                                                <dt class="inline font-semibold text-ink/45">{{ $group }}:</dt>
                                                <dd class="inline">{{ $options->pluck('name')->join(', ') }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                                @if(!empty($item['notes']))
                                    <p class="mt-2 text-xs italic text-ink/50">Note: {{ $item['notes'] }}</p>
                                @endif
                                <a href="{{ route('menu.show', $item['slug']) }}" class="mt-3 inline-block text-xs font-bold text-box hover:underline">
                                    Customize another {{ $item['name'] }} →
                                </a>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-stretch gap-3 sm:items-end">
                            <p class="text-right text-lg font-bold text-munch">{{ number_format($item['line_total'], 0) }} RWF</p>

                            <form
                                method="POST"
                                action="{{ route('cart.update', $item['key']) }}"
                                class="flex items-center gap-2"
                                x-data="{ qty: {{ (int) $item['quantity'] }} }"
                                x-ref="qtyForm"
                            >
                                @csrf
                                @method('PATCH')
                                <div class="flex items-center overflow-hidden rounded-2xl border border-munch/15 bg-cream/70">
                                    <button type="button" class="px-3 py-2 text-lg font-bold text-munch hover:bg-munch/5" @click="qty = Math.max(1, qty - 1); $nextTick(() => $refs.qtyForm.requestSubmit())">−</button>
                                    <input type="number" name="quantity" x-model.number="qty" min="1" max="20" class="w-12 border-0 bg-transparent text-center text-sm font-bold text-munch focus:ring-0" @change="$refs.qtyForm.requestSubmit()">
                                    <button type="button" class="px-3 py-2 text-lg font-bold text-munch hover:bg-munch/5" @click="qty = Math.min(20, qty + 1); $nextTick(() => $refs.qtyForm.requestSubmit())">+</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('cart.destroy', $item['key']) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            <a href="{{ route('menu.index') }}" class="flex w-full items-center justify-center gap-3 rounded-[28px] border border-dashed border-munch/20 bg-white/70 px-5 py-5 text-center transition hover:border-box/40 hover:bg-box/5">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-box/10 text-xl font-bold text-box">+</span>
                <span>
                    <span class="block font-extrabold text-munch">Want another sandwich?</span>
                    <span class="block text-sm text-ink/55">Add a different product — your current items stay in the cart</span>
                </span>
            </a>
        </div>

        @if($suggestions->isNotEmpty())
            <section class="mt-10">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-extrabold text-munch">Add something else</h2>
                        <p class="mt-1 text-sm text-ink/50">Tap to customize and add to this order</p>
                    </div>
                    <a href="{{ route('menu.index') }}" class="text-sm font-bold text-box hover:underline">Full menu</a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($suggestions as $product)
                        <a href="{{ route('menu.show', $product->slug) }}" class="card-surface group overflow-hidden transition hover:-translate-y-0.5">
                            <div class="aspect-[4/3] bg-cover bg-center transition duration-500 group-hover:scale-105" style="background-image: url('{{ asset($product->image ?: 'images/sandwiches/classic.jpg') }}')"></div>
                            <div class="p-4">
                                <p class="font-semibold text-munch">{{ $product->name }}</p>
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-box">{{ $product->formattedPrice() }}</span>
                                    <span class="text-xs font-bold text-munch/60 group-hover:text-box">Add →</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-8 card-surface sticky bottom-4 z-10 p-6 shadow-lg">
            <div class="flex items-center justify-between text-sm">
                <span class="text-ink/60">Subtotal · {{ $count }} {{ Str::plural('item', $count) }}</span>
                <span class="text-lg font-bold text-munch">{{ number_format($subtotal, 0) }} RWF</span>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <a href="{{ route('menu.index') }}" class="btn-secondary w-full text-center">Add more items</a>
                <a href="{{ route('checkout.create') }}" class="btn-primary w-full text-center">Proceed to checkout</a>
            </div>
        </div>
    @endif
</div>
@endsection
