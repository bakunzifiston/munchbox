@extends('layouts.customer')

@section('title', 'Menu')

@section('content')
<div class="menu-page">
    {{-- Brand intro — text only, no banner image --}}
    <section class="relative overflow-hidden border-b border-munch/10">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(216,80,0,0.10),_transparent_50%),radial-gradient(ellipse_at_bottom_left,_rgba(16,48,24,0.07),_transparent_45%)]"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
            <div class="menu-fade-in max-w-xl">
                <div class="mb-3 flex items-center gap-3">
                    <img src="{{ asset('images/brand/icon.svg') }}" alt="" class="h-11 w-11 drop-shadow-sm">
                    <span class="rounded-full bg-box/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-box">Fresh or toasted</span>
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    <span class="text-munch">Munch</span><span class="text-box">Box</span>
                </h1>
                <p class="mt-2 text-base font-medium text-munch/80">Unbox the Flavor.</p>
                <p class="mt-2 text-sm leading-relaxed text-ink/55">
                    Hand-stacked sandwiches with real ingredients — customize every bite and get it delivered across Kigali.
                </p>
            </div>
        </div>
    </section>

    {{-- Sticky category filters --}}
    <div class="sticky top-16 z-30 border-b border-munch/10 bg-cream/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl gap-2 overflow-x-auto px-4 py-3 sm:px-6 scrollbar-none">
            <a href="{{ route('home') }}"
               class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition {{ ! $activeSlug ? 'bg-munch text-white shadow-sm' : 'bg-white text-munch ring-1 ring-munch/10 hover:ring-box/30' }}">
                All
            </a>
            @foreach($categories as $category)
                <a href="{{ route('home', ['category' => $category->slug]) }}"
                   class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition {{ $activeSlug === $category->slug ? 'bg-munch text-white shadow-sm' : 'bg-white text-munch ring-1 ring-munch/10 hover:ring-box/30' }}">
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

            <section class="mb-14 last:mb-4">
                <div class="mb-5 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-extrabold text-munch sm:text-3xl">{{ $category->name }}</h2>
                        @if($category->description)
                            <p class="mt-1 text-sm text-ink/50">{{ $category->description }}</p>
                        @endif
                    </div>
                    <span class="hidden text-xs font-semibold uppercase tracking-wider text-ink/35 sm:inline">
                        {{ $category->products->count() }} {{ Str::plural('item', $category->products->count()) }}
                    </span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($category->products as $index => $product)
                        <article class="menu-card group menu-fade-in" style="animation-delay: {{ min($index * 0.05, 0.25) }}s">
                            <a href="{{ route('menu.show', $product->slug) }}" class="block overflow-hidden">
                                <div class="relative aspect-[4/3] overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-cover bg-center transition duration-700 ease-out group-hover:scale-110"
                                        style="background-image: url('{{ asset($product->image ?: 'images/sandwiches/classic.jpg') }}')"
                                    ></div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-munch-deep/70 via-transparent to-transparent opacity-80"></div>
                                    <div class="absolute left-3 top-3">
                                        @if($product->is_featured)
                                            <span class="rounded-full bg-box px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">Popular</span>
                                        @elseif(! $product->is_customizable)
                                            <span class="rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-munch shadow-sm">Add-on</span>
                                        @endif
                                    </div>
                                    <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between gap-2">
                                        <p class="text-lg font-extrabold leading-tight text-white drop-shadow">{{ $product->name }}</p>
                                        <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-extrabold text-munch shadow-sm">
                                            {{ $product->formattedPrice() }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <div class="flex flex-1 flex-col p-4">
                                <p class="line-clamp-2 flex-1 text-sm leading-relaxed text-ink/55">{{ $product->description }}</p>
                                <a href="{{ route('menu.show', $product->slug) }}" class="btn-primary mt-4 w-full">
                                    {{ $product->is_customizable ? 'Customize & order' : 'Add to order' }}
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-[28px] border border-dashed border-munch/15 bg-white/60 p-12 text-center text-ink/45">
                            Nothing in this category yet.
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach

        @if(! $activeSlug && $extras->isNotEmpty())
            <section class="overflow-hidden rounded-[32px] bg-munch text-white">
                <div class="grid lg:grid-cols-2">
                    <div class="relative min-h-[220px] bg-cover bg-center" style="background-image: url('{{ asset($extras->first()->image ?: 'images/sandwiches/fries.jpg') }}')">
                        <div class="absolute inset-0 bg-gradient-to-r from-munch/40 to-munch/10 lg:bg-gradient-to-l"></div>
                    </div>
                    <div class="flex flex-col justify-center p-8 sm:p-10">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-box-soft">Complete the meal</p>
                        <h2 class="mt-2 text-3xl font-extrabold">{{ $extras->first()->name }}</h2>
                        <p class="mt-3 text-sm text-white/70">{{ $extras->first()->description }}</p>
                        <div class="mt-6 flex flex-wrap items-center gap-3">
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
