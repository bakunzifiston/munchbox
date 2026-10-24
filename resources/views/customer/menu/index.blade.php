@extends('layouts.customer')

@section('title', 'Menu')

@section('content')
<section class="border-b border-munch/10">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-box">Unbox the Flavor.</p>
        <h1 class="mt-2 max-w-2xl text-3xl font-extrabold leading-tight text-munch sm:text-4xl">Signature sandwiches, served fresh or toasted.</h1>
    </div>
</section>

@if($categories->count() > 1)
    <nav class="sticky top-16 z-30 border-b border-munch/10 bg-cream/95 backdrop-blur-md" aria-label="Menu sections">
        <div class="mx-auto flex max-w-6xl gap-2 overflow-x-auto px-4 py-2.5 sm:px-6 scrollbar-none">
            @foreach($categories as $category)
                <a href="#{{ $category->slug }}" class="chip-filter chip-filter-idle">{{ $category->name }}</a>
            @endforeach
        </div>
    </nav>
@endif

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
    @forelse($categories as $category)
        <section id="{{ $category->slug }}" class="mb-14 scroll-mt-32 last:mb-0">
            <div class="mb-5 flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-extrabold text-munch sm:text-3xl">{{ $category->name }}</h2>
                    @if($category->description)
                        <p class="mt-1 text-sm text-ink/55">{{ $category->description }}</p>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                @foreach($category->products as $index => $product)
                    @php
                        $freshOrToasted = $product->optionGroups->contains(fn ($group) => $group->type === \App\Enums\OptionType::Style);
                    @endphp
                    <article class="menu-card group menu-fade-in" style="animation-delay: {{ min($index * 0.05, 0.25) }}s">
                        <a href="{{ route('menu.show', $product->slug) }}" class="block" tabindex="-1" aria-hidden="true">
                            <x-product-image :image="$product->image" :name="$product->name" class="aspect-[4/3]">
                                @if($product->is_featured)
                                    <span class="absolute left-3 top-3 rounded-full bg-box px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white shadow-sm">Popular</span>
                                @endif
                            </x-product-image>
                        </a>
                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-lg font-extrabold leading-snug text-munch">
                                    <a href="{{ route('menu.show', $product->slug) }}" class="hover:text-box">{{ $product->name }}</a>
                                </h3>
                                <span class="shrink-0 pt-0.5 text-base font-extrabold text-box">{{ $product->formattedPrice() }}</span>
                            </div>
                            @if($product->description)
                                <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink/60">{{ $product->description }}</p>
                            @endif
                            @if($freshOrToasted)
                                <p class="mt-3 text-xs font-semibold text-munch/55">Fresh or toasted</p>
                            @endif

                            <div class="mt-auto pt-5">
                                @if($product->is_customizable)
                                    <a href="{{ route('menu.show', $product->slug) }}" class="btn-primary w-full">Customize &amp; order</a>
                                @else
                                    <form method="POST" action="{{ route('cart.store', $product) }}">
                                        @csrf
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn-primary w-full">Add to order</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card-surface p-12 text-center text-ink/55">
            The menu is being updated. Please check back shortly.
        </div>
    @endforelse
</div>
@endsection
