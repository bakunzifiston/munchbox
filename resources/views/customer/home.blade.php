@extends('layouts.customer')

@section('title', 'Home')

@section('content')
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('/images/sandwiches/hero.jpg')"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-munch-deep/95 via-munch/85 to-munch/35"></div>
    <div class="relative mx-auto flex min-h-[88vh] max-w-6xl flex-col justify-end px-4 pb-16 pt-28 sm:px-6 sm:pb-20">
        <img src="{{ asset('images/brand/icon.svg') }}" alt="" class="mb-4 h-14 w-14 rounded-2xl bg-white/95 p-1 shadow-sm">
        <p class="font-extrabold leading-none tracking-tight text-5xl sm:text-7xl md:text-8xl">
            <span class="text-white">Munch</span><span class="text-box">Box</span>
        </p>
        <h1 class="mt-4 max-w-xl text-2xl font-semibold text-white/95 sm:text-3xl">{{ $tagline }}</h1>
        <p class="mt-3 max-w-lg text-sm text-white/75 sm:text-base">Signature sandwiches with proteins, fresh toppings, sauces, and bread — served fresh or toasted. Fast delivery across Kigali.</p>
        <div class="mt-5 inline-flex w-fit items-center rounded-full bg-box px-4 py-2 text-xs font-bold uppercase tracking-wide text-white">
            Served Fresh or Toasted
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('menu.index') }}" class="btn-primary">Browse menu</a>
            <a href="tel:+250799303456" class="btn-secondary border-white/30 bg-transparent text-white hover:bg-white/10">+250 799 303 456</a>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <div class="mb-8 flex items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-box">Signature Sandwiches</p>
            <h2 class="text-3xl font-extrabold text-munch sm:text-4xl">Crowd favorites</h2>
        </div>
        <a href="{{ route('menu.index') }}" class="btn-ghost">Full menu →</a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($featured as $product)
            <a href="{{ route('menu.show', $product->slug) }}" class="card-surface group overflow-hidden transition hover:-translate-y-1">
                <div class="aspect-[4/3] bg-cover bg-center" style="background-image: url('{{ asset($product->image ?: 'images/sandwiches/classic.jpg') }}')"></div>
                <div class="p-4">
                    <p class="text-xl font-extrabold text-munch group-hover:text-box">{{ $product->name }}</p>
                    <p class="mt-1 line-clamp-2 text-sm text-ink/60">{{ $product->description }}</p>
                    <p class="mt-3 text-sm font-bold text-munch">{{ $product->formattedPrice() }}</p>
                </div>
            </a>
        @empty
            <p class="text-ink/60">Menu coming soon.</p>
        @endforelse
    </div>
</section>

<section class="border-y border-munch/10 bg-white/60">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-16 sm:grid-cols-3 sm:px-6">
        <div>
            <p class="text-2xl font-extrabold text-munch">Customize</p>
            <p class="mt-2 text-sm text-ink/65">Proteins, fresh toppings, sauces, and bread — just like the menu.</p>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-munch">Fresh or toasted</p>
            <p class="mt-2 text-sm text-ink/65">Choose how you want every signature sandwich served.</p>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-munch">Fast delivery</p>
            <p class="mt-2 text-sm text-ink/65">Call <a class="font-semibold text-box" href="tel:+250799303456">+250 799 303 456</a> or order online.</p>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <div class="card-surface overflow-hidden lg:grid lg:grid-cols-2">
        <div class="min-h-[240px] bg-cover bg-center" style="background-image: url('/images/sandwiches/fries.jpg')"></div>
        <div class="flex flex-col justify-center p-8 sm:p-10">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-box">Add-on</p>
            <h2 class="mt-2 text-3xl font-extrabold text-munch">Fries & Soda Extra</h2>
            <p class="mt-3 text-ink/65">Pair any sandwich with crispy fries and a chilled soda.</p>
            <p class="mt-4 text-2xl font-bold text-munch">3,000 RWF</p>
            <a href="{{ route('menu.show', 'fries-soda-extra') }}" class="btn-primary mt-6 w-fit">Add to order</a>
        </div>
    </div>
</section>
@endsection
