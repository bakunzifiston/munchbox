@extends('layouts.customer')

@section('title', 'Cart')

@section('content')
@php
    $belowMinimum = $minOrder > 0 && $subtotal < $minOrder;
@endphp

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-extrabold text-munch sm:text-4xl">Your cart</h1>
            @if($items->isNotEmpty())
                <p class="mt-1 text-sm text-ink/60">{{ $count }} {{ Str::plural('item', $count) }}</p>
            @endif
        </div>
        @if($items->isNotEmpty())
            <form method="POST" action="{{ route('cart.clear') }}" onsubmit="return confirm('Remove everything from your cart?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-ghost text-ink/60 hover:text-red-700">Clear cart</button>
            </form>
        @endif
    </div>

    @foreach($notices as $notice)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">{{ $notice }}</div>
    @endforeach

    @if($items->isEmpty())
        <div class="card-surface mt-8 p-10 text-center sm:p-12">
            <p class="text-2xl font-extrabold text-munch">Your cart is empty</p>
            <p class="mt-2 text-ink/60">Pick a sandwich and make it yours.</p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">Browse the menu</a>
        </div>
    @else
        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_340px]">
            <ul class="space-y-3">
                @foreach($items as $item)
                    @php
                        $groupedOptions = collect($item['options'] ?? [])->groupBy('group');
                        $paidExtras = collect($item['options'] ?? [])->filter(fn ($o) => (float) $o['price_adjustment'] > 0);
                    @endphp
                    <li class="card-surface p-4 sm:p-5">
                        <div class="flex gap-4">
                            <a href="{{ route('menu.show', $item['slug']) }}" class="shrink-0" tabindex="-1" aria-hidden="true">
                                <x-product-image :image="$item['image']" :name="$item['name']" class="h-20 w-20 rounded-2xl sm:h-24 sm:w-24" />
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('menu.show', $item['slug']) }}" class="font-extrabold leading-snug text-munch hover:text-box">{{ $item['name'] }}</a>
                                    <p class="shrink-0 font-extrabold text-munch">{{ \App\Support\Money::format($item['line_total']) }}</p>
                                </div>
                                <p class="mt-0.5 text-xs text-ink/50">
                                    {{ \App\Support\Money::format($item['unit_with_options']) }} each
                                    @if($paidExtras->isNotEmpty())
                                        · includes {{ $paidExtras->map(fn ($o) => $o['name'].' +'.\App\Support\Money::format($o['price_adjustment']))->join(', ') }}
                                    @endif
                                </p>

                                @if($groupedOptions->isNotEmpty())
                                    <dl class="mt-2 space-y-0.5 text-sm text-ink/60">
                                        @foreach($groupedOptions as $group => $options)
                                            <div>
                                                <dt class="inline font-semibold text-ink/45">{{ $group }}:</dt>
                                                <dd class="inline">{{ $options->pluck('name')->join(', ') }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                                @if(! empty($item['notes']))
                                    <p class="mt-2 text-sm italic text-ink/55">“{{ $item['notes'] }}”</p>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    <form
                                        method="POST"
                                        action="{{ route('cart.update', $item['key']) }}"
                                        x-data="{ qty: {{ (int) $item['quantity'] }} }"
                                        x-ref="qtyForm"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <div class="qty-stepper">
                                            <button type="button" aria-label="Decrease quantity" :disabled="qty <= 1" @click="qty = Math.max(1, qty - 1); $nextTick(() => $refs.qtyForm.requestSubmit())">−</button>
                                            <input type="number" name="quantity" x-model.number="qty" value="{{ (int) $item['quantity'] }}" min="1" max="{{ \App\Services\CartService::MAX_QUANTITY }}" aria-label="Quantity" @change="$refs.qtyForm.requestSubmit()">
                                            <button type="button" aria-label="Increase quantity" :disabled="qty >= {{ \App\Services\CartService::MAX_QUANTITY }}" @click="qty = Math.min({{ \App\Services\CartService::MAX_QUANTITY }}, qty + 1); $nextTick(() => $refs.qtyForm.requestSubmit())">+</button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('cart.destroy', $item['key']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex min-h-[44px] items-center text-sm font-semibold text-red-700 hover:underline">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="card-surface p-5 lg:sticky lg:top-24">
                <h2 class="text-lg font-extrabold text-munch">Order summary</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink/60">Subtotal</dt>
                        <dd class="font-bold text-munch">{{ \App\Support\Money::format($subtotal) }}</dd>
                    </div>
                    @if($deliveryFee > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink/60">Delivery</dt>
                            <dd class="text-ink/60">{{ \App\Support\Money::format($deliveryFee) }} <span class="text-xs">(pickup free)</span></dd>
                        </div>
                    @endif
                </dl>

                @if($belowMinimum)
                    <p class="mt-4 rounded-2xl bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        The minimum order is {{ \App\Support\Money::format($minOrder) }}. Add {{ \App\Support\Money::format($minOrder - $subtotal) }} more to check out.
                    </p>
                @endif

                <div class="mt-5 grid gap-2">
                    @if($belowMinimum)
                        <button type="button" class="btn-primary w-full" disabled>Proceed to checkout</button>
                    @else
                        <a href="{{ route('checkout.create') }}" class="btn-primary w-full">Proceed to checkout</a>
                    @endif
                    <a href="{{ route('home') }}" class="btn-secondary w-full">Continue shopping</a>
                </div>
            </aside>
        </div>

        @if($suggestions->isNotEmpty())
            <section class="mt-12">
                <h2 class="mb-4 text-xl font-extrabold text-munch">Add something else</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach($suggestions as $product)
                        <a href="{{ route('menu.show', $product->slug) }}" class="menu-card group">
                            <x-product-image :image="$product->image" :name="$product->name" class="aspect-[16/10]" />
                            <div class="flex items-start justify-between gap-3 p-4">
                                <p class="font-bold text-munch">{{ $product->name }}</p>
                                <p class="shrink-0 text-sm font-bold text-box">{{ $product->formattedPrice() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</div>
@endsection
