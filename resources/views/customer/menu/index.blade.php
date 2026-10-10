@extends('layouts.customer')

@section('title', 'Menu')

@section('content')
<div class="menu-page">
    <div class="sticky top-[4.25rem] z-30 border-b border-munch/10 bg-cream/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl gap-2 overflow-x-auto px-4 py-3 sm:px-6 scrollbar-none">
            <a href="{{ route('home') }}"
               class="chip-filter {{ ! $activeSlug ? 'chip-filter-active' : 'chip-filter-idle' }}">
                All
            </a>
            @foreach($categories as $category)
                <a href="{{ route('home', ['category' => $category->slug]) }}"
                   class="chip-filter {{ $activeSlug === $category->slug ? 'chip-filter-active' : 'chip-filter-idle' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        @foreach($categories as $category)
            @if($activeSlug && $activeSlug !== $category->slug)
                @continue
            @endif

            <section class="mb-16 last:mb-6">
                <div class="mb-7 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-3xl font-extrabold text-munch sm:text-4xl">{{ $category->name }}</h2>
                        @if($category->description)
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink/55 sm:text-base">{{ $category->description }}</p>
                        @endif
                    </div>
                    <span class="hidden text-xs font-semibold uppercase tracking-[0.16em] text-ink/35 sm:inline">
                        {{ $category->products->count() }} {{ Str::plural('item', $category->products->count()) }}
                    </span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                    @forelse($category->products as $index => $product)
                        <article class="menu-card group menu-fade-in" style="animation-delay: {{ min($index * 0.05, 0.25) }}s">
                            <a href="{{ route('menu.show', $product->slug) }}" class="block">
                                <div class="menu-card-media">
                                    <img
                                        src="{{ asset($product->image ?: 'images/sandwiches/classic.jpg') }}"
                                        alt="{{ $product->name }}"
                                        class="menu-card-media-img"
                                        loading="lazy"
                                    >
                                    <div class="absolute left-3 top-3">
                                        @if($product->is_featured)
                                            <span class="rounded-full bg-box px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">Popular</span>
                                        @elseif(! $product->is_customizable)
                                            <span class="rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-munch shadow-sm">Add-on</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-lg font-extrabold leading-tight text-munch">
                                        <a href="{{ route('menu.show', $product->slug) }}" class="hover:text-box">{{ $product->name }}</a>
                                    </h3>
                                    <span class="shrink-0 text-sm font-extrabold text-box">{{ $product->formattedPrice() }}</span>
                                </div>
                                <p class="mt-2 line-clamp-2 flex-1 text-sm leading-relaxed text-ink/55">{{ $product->description }}</p>
                                <a href="{{ route('menu.show', $product->slug) }}" class="btn-primary mt-5 w-full">
                                    {{ $product->is_customizable ? 'Customize & order' : 'Add to order' }}
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-[28px] border border-dashed border-munch/15 bg-white/70 p-12 text-center text-ink/45">
                            Nothing in this category yet.
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach

        @if(! $activeSlug && $extras->isNotEmpty())
            <section class="overflow-hidden rounded-[36px] bg-munch text-white shadow-menu">
                <div class="grid lg:grid-cols-2">
                    <div class="relative min-h-[260px] overflow-hidden">
                        <img
                            src="{{ asset($extras->first()->image ?: 'images/sandwiches/fries.jpg') }}"
                            alt="{{ $extras->first()->name }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-gradient-to-r from-munch/30 to-transparent lg:bg-gradient-to-l"></div>
                    </div>
                    <div class="flex flex-col justify-center p-8 sm:p-12">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-box-soft">Complete the meal</p>
                        <h2 class="mt-3 font-display text-3xl font-extrabold sm:text-4xl">{{ $extras->first()->name }}</h2>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-white/70">{{ $extras->first()->description }}</p>
                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <span class="text-2xl font-extrabold">{{ $extras->first()->formattedPrice() }}</span>
                            <a href="{{ route('menu.show', $extras->first()->slug) }}" class="btn-primary">Add fries & soda</a>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
