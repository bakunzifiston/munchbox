<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) — MunchBox</title>
    <link rel="icon" href="{{ asset('images/brand/app-icon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data>
    <header class="sticky top-0 z-40 border-b border-munch/8 bg-cream/85 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="group shrink-0">
                <x-brand-mark size="md" />
            </a>

            <nav class="hidden items-center gap-1 md:flex">
                <a href="{{ route('home') }}" class="site-nav-link {{ request()->routeIs('home') || request()->routeIs('menu.*') ? 'site-nav-link-active' : '' }}">Menu</a>
                <a href="{{ route('orders.track') }}" class="site-nav-link {{ request()->routeIs('orders.track') ? 'site-nav-link-active' : '' }}">Track order</a>
                @auth
                    <a href="{{ route('orders.index') }}" class="site-nav-link {{ request()->routeIs('orders.index') ? 'site-nav-link-active' : '' }}">My orders</a>
                    <a href="{{ route('account.show') }}" class="site-nav-link {{ request()->routeIs('account.*') ? 'site-nav-link-active' : '' }}">Account</a>
                    @if(auth()->user()->isStaff())
                        <a href="{{ route('admin.dashboard') }}" class="site-nav-link">Admin</a>
                    @endif
                @endauth
                <a href="{{ route('cart.index') }}" class="relative site-nav-link {{ request()->routeIs('cart.*') ? 'site-nav-link-active' : '' }}">
                    Cart
                    @if(($cartCount ?? 0) > 0)
                        <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-box px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>
                <a href="{{ route('home') }}" class="site-nav-link">Order now</a>
            </nav>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="hidden max-w-[190px] items-center gap-2 rounded-full border border-munch/10 bg-white px-3 py-2 text-left shadow-sm transition hover:border-box/40 sm:flex"
                    @click="$dispatch('open-location-modal')"
                    title="Change delivery location"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-box">
                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.145 16.978 16.978 0 002.592-1.932c1.76-1.644 3.793-4.29 3.793-7.778A7 7 0 0010 1a7 7 0 00-7 7c0 3.488 2.033 6.134 3.793 7.778a16.978 16.978 0 002.873 2.077 5.741 5.741 0 00.281.145l.018.008.006.003zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-bold text-munch" data-location-label>Kigali City</span>
                        <span class="block truncate text-[10px] text-ink/50" data-location-detail>Amahoro, Kigali</span>
                    </span>
                </button>

                @guest
                    <a href="{{ route('login') }}" class="btn-primary !px-4">Sign in</a>
                @endguest

                <a href="{{ route('cart.index') }}" class="relative btn-secondary !px-4 md:hidden">
                    <span>Cart</span>
                    @if(($cartCount ?? 0) > 0)
                        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-box px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>

                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-munch shadow-sm ring-1 ring-munch/10 md:hidden" x-data @click="$dispatch('toggle-mobile')" aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
            </div>
        </div>

        <div x-data="{ open: false }" @toggle-mobile.window="open = !open" x-show="open" x-cloak class="border-t border-munch/10 bg-cream/95 px-4 py-3 md:hidden">
            <div class="flex flex-col gap-1">
                <button type="button" class="btn-ghost justify-start" @click="open = false; $dispatch('open-location-modal')">
                    <span data-location-label>Kigali City</span>
                </button>
                <a href="{{ route('home') }}" class="btn-ghost justify-start" @click="open = false">Menu</a>
                <a href="{{ route('orders.track') }}" class="btn-ghost justify-start" @click="open = false">Track order</a>
                <a href="{{ route('cart.index') }}" class="btn-ghost justify-start" @click="open = false">Cart</a>
                <a href="{{ route('home') }}" class="btn-ghost justify-start" @click="open = false">Order now</a>
                @auth
                    <a href="{{ route('orders.index') }}" class="btn-ghost justify-start" @click="open = false">My orders</a>
                    <a href="{{ route('account.show') }}" class="btn-ghost justify-start" @click="open = false">Account</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary mt-2 justify-center" @click="open = false">Sign in</a>
                    <a href="{{ route('register') }}" class="btn-ghost justify-start" @click="open = false">Create account</a>
                @endauth
            </div>
        </div>
    </header>

    <x-location-modal />

    @if(session('success') || session('error'))
        <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
            @if(session('success'))
                <div class="rounded-2xl border border-munch/20 bg-sage px-4 py-3 text-sm font-medium text-munch">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mt-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
            @endif
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="mt-20 border-t border-white/10 bg-munch-deep text-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:grid-cols-3 sm:px-6">
            <div>
                <x-brand-mark size="lg" inverse />
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-white/70">Unbox the Flavor. Signature sandwiches served fresh or toasted across Kigali.</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/45">Explore</p>
                <div class="mt-4 flex flex-col gap-2.5 text-sm">
                    <a href="{{ route('menu.index') }}" class="transition hover:text-box-soft">Menu</a>
                    <a href="{{ route('orders.track') }}" class="transition hover:text-box-soft">Track order</a>
                    <a href="{{ route('checkout.create') }}" class="transition hover:text-box-soft">Checkout</a>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/45">Delivery</p>
                <p class="mt-4 text-sm text-white/80">Kigali, Rwanda</p>
                <a href="tel:0799303456" class="mt-1 block text-sm font-semibold text-box-soft transition hover:text-white">0799303456</a>
                <p class="mt-1 text-sm text-white/70">Mon–Sun 09:00–22:00</p>
            </div>
        </div>
        <div class="border-t border-white/10 px-4 py-4 text-center text-xs text-white/45">
            Unbox the Flavor. · © {{ date('Y') }} MunchBox · Prices in RWF
        </div>
    </footer>
</body>
</html>
