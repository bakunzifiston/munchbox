<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — MunchBox Admin</title>
    <link rel="icon" href="{{ asset('images/brand/app-icon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F6F4EF] text-ink" x-data="{ sidebarOpen: false, searchOpen: false }">
    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-munch-deep/40 lg:hidden"
        @click="sidebarOpen = false"
        x-cloak
    ></div>

    <div class="flex min-h-screen">
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[272px] -translate-x-full flex-col bg-munch-deep transition duration-300 lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="flex h-[72px] items-center gap-3 border-b border-white/10 px-5">
                <img src="{{ asset('images/brand/icon.svg') }}" alt="" class="h-10 w-10 rounded-xl bg-white p-1">
                <div>
                    <p class="text-lg font-extrabold leading-none tracking-tight">
                        <span class="text-white">Munch</span><span class="text-box">Box</span>
                    </p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/45">Admin</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @php
                    $pendingBadge = \App\Models\Order::query()->pending()->count();
                    $lowStockBadge = \App\Models\InventoryItem::query()->lowStock()->count();

                    $links = [
                        ['admin.dashboard', 'Dashboard', 'dashboard', null],
                        ['admin.orders.index', 'Orders', 'orders', $pendingBadge],
                        ['admin.products.index', 'Menu & Products', 'products', null],
                        ['admin.categories.index', 'Categories', 'categories', null],
                        ['admin.options.index', 'Menu Options', 'options', null],
                        ['admin.ingredients.index', 'Ingredients', 'ingredients', null],
                        ['admin.inventory.index', 'Inventory', 'inventory', $lowStockBadge],
                        ['admin.customers.index', 'Customers', 'customers', null],
                        ['admin.deliveries.index', 'Delivery', 'delivery', null],
                        ['admin.reports.index', 'Reports', 'reports', null],
                        ['admin.settings.edit', 'Settings', 'settings', null],
                    ];

                    $isActive = function (string $route) {
                        if ($route === 'admin.dashboard') {
                            return request()->routeIs('admin.dashboard');
                        }
                        $pattern = str_replace(['.index', '.edit'], '.*', $route);
                        return request()->routeIs($route) || request()->routeIs($pattern);
                    };
                @endphp

                @foreach($links as [$route, $label, $icon, $badge])
                    <a href="{{ route($route) }}"
                       @click="sidebarOpen = false"
                       class="group flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition {{ $isActive($route) ? 'bg-white/12 text-white' : 'text-white/65 hover:bg-white/8 hover:text-white' }}">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl {{ $isActive($route) ? 'bg-box text-white' : 'bg-white/8 text-white/70 group-hover:bg-white/12' }}">
                            @include('layouts.partials.admin-icon', ['name' => $icon])
                        </span>
                        <span class="flex-1">{{ $label }}</span>
                        @if($badge)
                            <span class="rounded-full bg-box px-2 py-0.5 text-[10px] font-bold text-white">{{ $badge }}</span>
                        @endif
                    </a>
                @endforeach

                @can('manage-users')
                    <a href="{{ route('admin.users.index') }}"
                       @click="sidebarOpen = false"
                       class="group flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-white/12 text-white' : 'text-white/65 hover:bg-white/8 hover:text-white' }}">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl {{ request()->routeIs('admin.users.*') ? 'bg-box text-white' : 'bg-white/8 text-white/70' }}">
                            @include('layouts.partials.admin-icon', ['name' => 'users'])
                        </span>
                        <span>Users & Roles</span>
                    </a>
                @endcan
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="mb-3 flex items-center gap-3 rounded-2xl bg-white/8 px-3 py-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-box text-sm font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-white/50">{{ auth()->user()->role?->name ?? 'Staff' }}</p>
                    </div>
                </div>
                <a href="{{ route('home') }}" class="mb-1 flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-white/65 transition hover:bg-white/8 hover:text-white">
                    <span>←</span> Storefront
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-semibold text-white/65 transition hover:bg-white/8 hover:text-white">
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 border-b border-munch/10 bg-[#F6F4EF]/90 backdrop-blur-md">
                <div class="flex h-[72px] items-center gap-3 px-4 sm:px-6">
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-munch/10 bg-white text-munch lg:hidden" @click="sidebarOpen = !sidebarOpen">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-lg font-extrabold text-munch">@yield('heading', 'Dashboard')</p>
                        <p class="truncate text-xs text-ink/45">@yield('subheading', 'Operations overview')</p>
                    </div>

                    <form action="{{ route('admin.orders.index') }}" method="GET" class="hidden max-w-xs flex-1 md:block">
                        <label class="relative block">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink/35">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 5.64 5.64a7.5 7.5 0 0 0 10.61 10.61Z" /></svg>
                            </span>
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search orders..." class="w-full rounded-full border-0 bg-white py-2.5 pl-10 pr-4 text-sm shadow-sm ring-1 ring-munch/10 placeholder:text-ink/35 focus:ring-2 focus:ring-box/30">
                        </label>
                    </form>

                    <a href="{{ route('home') }}" class="hidden items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-bold text-munch shadow-sm ring-1 ring-munch/10 transition hover:ring-box/30 sm:inline-flex">
                        Storefront
                    </a>

                    <div class="relative" x-data="{ open: false }">
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-munch shadow-sm ring-1 ring-munch/10" @click="open = !open">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                            @if($pendingBadge > 0)
                                <span class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-box ring-2 ring-white"></span>
                            @endif
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-72 rounded-2xl bg-white p-3 shadow-xl ring-1 ring-munch/10">
                            <p class="px-2 pb-2 text-xs font-bold uppercase tracking-wider text-ink/40">Notifications</p>
                            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="block rounded-xl px-3 py-2.5 hover:bg-sage/60">
                                <p class="text-sm font-semibold text-munch">{{ $pendingBadge }} pending order{{ $pendingBadge === 1 ? '' : 's' }}</p>
                                <p class="text-xs text-ink/50">Need confirmation</p>
                            </a>
                            <a href="{{ route('admin.inventory.index') }}" class="mt-1 block rounded-xl px-3 py-2.5 hover:bg-sage/60">
                                <p class="text-sm font-semibold text-munch">{{ $lowStockBadge }} low-stock item{{ $lowStockBadge === 1 ? '' : 's' }}</p>
                                <p class="text-xs text-ink/50">Inventory attention</p>
                            </a>
                        </div>
                    </div>

                    <div class="hidden items-center gap-2 rounded-full bg-white py-1.5 pl-1.5 pr-3 shadow-sm ring-1 ring-munch/10 sm:flex">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-munch text-xs font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="text-xs font-bold text-munch">{{ explode(' ', auth()->user()->name)[0] }}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                        @csrf
                        <button class="rounded-full px-3 py-2 text-xs font-bold text-ink/50 transition hover:bg-white hover:text-munch">Sign out</button>
                    </form>
                </div>
            </header>

            <div class="flex-1 p-4 sm:p-6 lg:p-8">
                @if(session('success'))
                    <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">
                        <p>Changes were not saved:</p>
                        <ul class="mt-1 list-disc pl-5">
                            @foreach($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
