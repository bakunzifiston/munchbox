@php
    $business = [
        'phone' => \App\Models\BusinessSetting::getValue('phone', config('menu.phone')),
        'hours' => \App\Models\BusinessSetting::getValue('opening_hours', 'Mon–Sun 09:00–22:00'),
        'address' => \App\Models\BusinessSetting::getValue('address', 'Kigali, Rwanda'),
        'tagline' => \App\Models\BusinessSetting::getValue('tagline', config('menu.tagline')),
    ];
    $phoneHref = 'tel:'.preg_replace('/[^0-9+]/', '', (string) $business['phone']);
    $onMenu = request()->routeIs('home') || request()->routeIs('menu.*');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Menu') — MunchBox</title>
    <meta name="description" content="MunchBox — signature sandwiches served fresh or toasted, delivered across Kigali.">
    <link rel="icon" href="{{ asset('images/brand/app-icon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col" x-data>
    <header class="sticky top-0 z-40 border-b border-munch/10 bg-cream/90 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:px-6">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="MunchBox home">
                <x-brand-mark size="sm" class="sm:hidden" />
                <x-brand-mark size="md" class="hidden sm:inline-flex" />
            </a>

            <nav class="ml-4 hidden items-center gap-1 md:flex" aria-label="Main">
                <a href="{{ route('home') }}" class="site-nav-link {{ $onMenu ? 'site-nav-link-active' : '' }}">Menu</a>
                <a href="{{ route('orders.track') }}" class="site-nav-link {{ request()->routeIs('orders.track') ? 'site-nav-link-active' : '' }}">Track order</a>
                @auth
                    <a href="{{ route('orders.index') }}" class="site-nav-link {{ request()->routeIs('orders.index') ? 'site-nav-link-active' : '' }}">My orders</a>
                @endauth
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <button
                    type="button"
                    class="hidden items-center gap-2 rounded-full px-3 py-1.5 text-left transition hover:bg-white lg:flex"
                    @click="$dispatch('open-location-modal')"
                    title="Change delivery area"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-box" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.145 16.978 16.978 0 002.592-1.932c1.76-1.644 3.793-4.29 3.793-7.778A7 7 0 0010 1a7 7 0 00-7 7c0 3.488 2.033 6.134 3.793 7.778a16.978 16.978 0 002.873 2.077 5.741 5.741 0 00.281.145l.018.008.006.003zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                    <span class="min-w-0 leading-tight">
                        <span class="block max-w-[150px] truncate text-xs font-bold text-munch" data-location-label>Kigali City</span>
                        <span class="block max-w-[150px] truncate text-[11px] text-ink/50" data-location-detail>Amahoro, Kigali</span>
                    </span>
                </button>

                <a href="{{ route('cart.index') }}" class="relative inline-flex h-10 items-center gap-2 rounded-full px-3 text-sm font-semibold text-munch transition hover:bg-white {{ request()->routeIs('cart.*') ? 'bg-white ring-1 ring-munch/10' : '' }}" aria-label="Cart, {{ $cartCount ?? 0 }} items">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>
                    <span class="hidden sm:inline">Cart</span>
                    @if(($cartCount ?? 0) > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-box px-1.5 text-[11px] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>

                @auth
                    <a href="{{ route('account.show') }}" class="site-nav-link hidden md:inline-flex {{ request()->routeIs('account.*') ? 'site-nav-link-active' : '' }}">Account</a>
                @else
                    <a href="{{ route('login') }}" class="site-nav-link hidden md:inline-flex">Sign in</a>
                @endauth

                @unless($onMenu)
                    <a href="{{ route('home') }}" class="btn-primary hidden !px-4 md:inline-flex">Order now</a>
                @endunless

                @auth
                    @if(auth()->user()->isStaff())
                        <a href="{{ route('admin.dashboard') }}" class="site-nav-link hidden md:inline-flex">Admin</a>
                    @endif
                @endauth

                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-munch transition hover:bg-white md:hidden" @click="$dispatch('toggle-mobile')" aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
            </div>
        </div>

        <div x-data="{ open: false }" @toggle-mobile.window="open = !open" @keydown.escape.window="open = false" x-show="open" x-cloak x-transition.opacity class="border-t border-munch/10 bg-cream px-4 pb-4 pt-2 md:hidden">
            <button type="button" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left hover:bg-white" @click="open = false; $dispatch('open-location-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0 text-box" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.145 16.978 16.978 0 002.592-1.932c1.76-1.644 3.793-4.29 3.793-7.778A7 7 0 0010 1a7 7 0 00-7 7c0 3.488 2.033 6.134 3.793 7.778a16.978 16.978 0 002.873 2.077 5.741 5.741 0 00.281.145l.018.008.006.003zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-munch" data-location-label>Kigali City</span>
                    <span class="block truncate text-xs text-ink/50" data-location-detail>Amahoro, Kigali</span>
                </span>
                <span class="text-xs font-bold text-box">Change</span>
            </button>
            <nav class="mt-1 grid gap-0.5" aria-label="Mobile">
                <a href="{{ route('home') }}" class="mobile-nav-link" @click="open = false">Menu</a>
                <a href="{{ route('orders.track') }}" class="mobile-nav-link" @click="open = false">Track order</a>
                @auth
                    <a href="{{ route('orders.index') }}" class="mobile-nav-link" @click="open = false">My orders</a>
                    <a href="{{ route('account.show') }}" class="mobile-nav-link" @click="open = false">Account</a>
                    @if(auth()->user()->isStaff())
                        <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link" @click="open = false">Admin</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="mobile-nav-link" @click="open = false">Sign in</a>
                    <a href="{{ route('register') }}" class="mobile-nav-link" @click="open = false">Create account</a>
                @endauth
            </nav>
        </div>
    </header>

    <x-location-modal />

    @if(session('success') || session('error'))
        <div class="mx-auto w-full max-w-6xl px-4 pt-4 sm:px-6" role="status">
            @if(session('success'))
                <div class="rounded-2xl border border-munch/15 bg-sage px-4 py-3 text-sm font-medium text-munch">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mt-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
            @endif
        </div>
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-16 bg-munch-deep text-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <x-brand-mark size="md" inverse />
                <p class="mt-3 text-sm font-semibold text-box-soft">{{ $business['tagline'] }}</p>
                <p class="mt-1 max-w-xs text-sm leading-relaxed text-white/65">Signature sandwiches served fresh or toasted across Kigali.</p>
            </div>
            <div>
                <p class="footer-heading">Explore</p>
                <ul class="mt-3 space-y-2 text-sm text-white/80">
                    <li><a href="{{ route('home') }}" class="transition hover:text-white">Menu</a></li>
                    <li><a href="{{ route('orders.track') }}" class="transition hover:text-white">Track order</a></li>
                    @if(($cartCount ?? 0) > 0)
                        <li><a href="{{ route('checkout.create') }}" class="transition hover:text-white">Checkout</a></li>
                    @endif
                </ul>
            </div>
            <div>
                <p class="footer-heading">Delivery</p>
                <ul class="mt-3 space-y-2 text-sm text-white/80">
                    <li>{{ $business['address'] }}</li>
                    <li>Amahoro, Kigali</li>
                    <li><a href="{{ $phoneHref }}" class="font-semibold text-box-soft transition hover:text-white">{{ $business['phone'] }}</a></li>
                    <li>{{ $business['hours'] }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <p class="mx-auto max-w-6xl px-4 py-4 text-xs text-white/45 sm:px-6">© {{ date('Y') }} MunchBox · Prices in RWF</p>
        </div>
    </footer>
</body>
</html>
