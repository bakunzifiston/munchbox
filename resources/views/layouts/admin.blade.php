@php
    $user = auth()->user();
    $metrics = app(\App\Services\SalesMetrics::class);
    $pendingBadge = $metrics->pendingCount();
    $lowStockBadge = $user->can('manage-stock') ? $metrics->stockAlertCount() : 0;

    // [route, label, icon, badge, ability, active pattern]
    $groups = [
        'Overview' => [
            ['admin.dashboard', 'Dashboard', 'dashboard', null, 'access-admin', 'admin.dashboard'],
        ],
        'Operations' => [
            ['admin.orders.index', 'Orders', 'orders', $pendingBadge, 'manage-orders', 'admin.orders.*'],
            ['admin.deliveries.index', 'Delivery', 'delivery', null, 'manage-orders', 'admin.deliveries.*'],
        ],
        'Menu' => [
            ['admin.products.index', 'Products', 'products', null, 'manage-catalog', 'admin.products.*'],
            ['admin.categories.index', 'Categories', 'categories', null, 'manage-catalog', 'admin.categories.*'],
            ['admin.options.index', 'Menu Options', 'options', null, 'manage-catalog', 'admin.options.*'],
        ],
        'Stock' => [
            ['admin.ingredients.index', 'Ingredients', 'ingredients', null, 'manage-stock', 'admin.ingredients.*'],
            ['admin.inventory.index', 'Inventory', 'inventory', $lowStockBadge, 'manage-stock', 'admin.inventory.*'],
        ],
        'Business' => [
            ['admin.customers.index', 'Customers', 'customers', null, 'view-customers', 'admin.customers.*'],
            ['admin.reports.index', 'Reports', 'reports', null, 'view-financials', 'admin.reports.*'],
        ],
        'Admin' => [
            ['admin.settings.edit', 'Settings', 'settings', null, 'manage-settings', 'admin.settings.*'],
            ['admin.users.index', 'Users & Roles', 'users', null, 'manage-users', 'admin.users.*'],
        ],
    ];
@endphp
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
<body
    class="min-h-screen bg-[#F6F4EF] text-ink"
    x-data="{
        drawer: false,
        collapsed: (() => { try { return localStorage.getItem('mb_admin_collapsed') === '1' } catch (e) { return false } })(),
        toggleCollapsed() { this.collapsed = !this.collapsed; try { localStorage.setItem('mb_admin_collapsed', this.collapsed ? '1' : '0') } catch (e) {} },
    }"
    @keydown.escape.window="drawer = false"
>
    <div x-show="drawer" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-munch-deep/40 lg:hidden" @click="drawer = false"></div>

    <div class="flex min-h-screen">
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-munch-deep text-white transition-[transform,width] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            :class="{ 'translate-x-0': drawer, 'lg:w-[72px]': collapsed }"
            aria-label="Admin navigation"
        >
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-4">
                <img src="{{ asset('images/brand/icon.svg') }}" alt="" class="h-9 w-9 shrink-0 rounded-lg bg-white p-1">
                <p class="truncate text-base font-extrabold tracking-tight" :class="collapsed && 'lg:hidden'">
                    Munch<span class="text-box">Box</span> <span class="ml-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/45">Admin</span>
                </p>
                <button type="button" class="ml-auto rounded-lg p-1.5 text-white/60 hover:bg-white/10 hover:text-white lg:hidden" @click="drawer = false" aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-3">
                @foreach($groups as $groupLabel => $links)
                    @php $visible = array_filter($links, fn ($link) => $user->can($link[4])); @endphp
                    @continue(empty($visible))
                    <p class="mb-1 mt-4 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-white/35 first:mt-0" :class="collapsed && 'lg:hidden'">{{ $groupLabel }}</p>
                    @foreach($visible as [$route, $label, $icon, $badge, $ability, $pattern])
                        @php $active = request()->routeIs($pattern); @endphp
                        <a href="{{ route($route) }}"
                           title="{{ $label }}"
                           class="relative mb-0.5 flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-white/12 text-white' : 'text-white/65 hover:bg-white/8 hover:text-white' }}"
                           @if($active) aria-current="page" @endif>
                            @if($active)<span class="absolute inset-y-1.5 left-0 w-1 rounded-r bg-box"></span>@endif
                            @include('layouts.partials.admin-icon', ['name' => $icon, 'class' => 'h-5 w-5 shrink-0'])
                            <span class="flex-1 truncate" :class="collapsed && 'lg:hidden'">{{ $label }}</span>
                            @if($badge)
                                <span class="rounded-full bg-box px-1.5 py-0.5 text-[10px] font-bold text-white" :class="collapsed && 'lg:absolute lg:right-1 lg:top-0.5'">{{ $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="shrink-0 border-t border-white/10 p-3">
                <div class="flex items-center gap-3 px-2 py-1.5" :class="collapsed && 'lg:justify-center lg:px-0'">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-box text-xs font-bold">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div class="min-w-0 flex-1" :class="collapsed && 'lg:hidden'">
                        <p class="truncate text-sm font-bold">{{ $user->name }}</p>
                        <p class="truncate text-xs text-white/50">{{ $user->role?->name ?? 'Staff' }}</p>
                    </div>
                </div>
                <div class="mt-1 grid gap-0.5">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" title="Open storefront" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-white/65 hover:bg-white/8 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        <span :class="collapsed && 'lg:hidden'">Storefront</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign out" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold text-white/65 hover:bg-white/8 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                            <span :class="collapsed && 'lg:hidden'">Sign out</span>
                        </button>
                    </form>
                    <button type="button" class="hidden items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold text-white/45 hover:bg-white/8 hover:text-white lg:flex" @click="toggleCollapsed()" :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5 shrink-0 transition" :class="collapsed && 'rotate-180'"><path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" /></svg>
                        <span :class="collapsed && 'lg:hidden'">Collapse</span>
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 border-b border-munch/10 bg-[#F6F4EF]/90 backdrop-blur-md">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
                    <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-munch/10 bg-white text-munch lg:hidden" @click="drawer = true" aria-label="Open menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-lg font-extrabold text-munch">@yield('heading', 'Dashboard')</h1>
                        <p class="hidden truncate text-xs text-ink/50 sm:block">@yield('subheading')</p>
                    </div>

                    @can('manage-orders')
                        <form action="{{ route('admin.orders.index') }}" method="GET" class="hidden w-64 md:block" role="search">
                            <label class="relative block">
                                <span class="sr-only">Search orders</span>
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink/35">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 5.64 5.64a7.5 7.5 0 0 0 10.61 10.61Z" /></svg>
                                </span>
                                <input type="search" name="q" value="{{ request()->routeIs('admin.orders.index') ? request('q') : '' }}" placeholder="Search orders: number, name, phone" class="w-full rounded-full border-0 bg-white py-2 pl-9 pr-4 text-sm shadow-sm ring-1 ring-munch/10 placeholder:text-ink/35 focus:ring-2 focus:ring-box/30">
                            </label>
                        </form>
                        <a href="{{ route('admin.orders.create') }}" class="btn-primary !min-h-[40px] !px-4 !py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <span class="hidden sm:inline">New order</span>
                        </a>
                    @endcan
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900" role="status">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900" role="alert">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900" role="alert">
                        <p>Changes were not saved:</p>
                        <ul class="mt-1 list-disc pl-5">
                            @foreach($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Ask before important actions (<form data-confirm="...">) and stop double submits.
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            if (form.method.toLowerCase() === 'get') return;
            const button = event.submitter;
            if (button) {
                setTimeout(() => { button.disabled = true; button.setAttribute('aria-busy', 'true'); }, 0);
            }
        });
        window.addEventListener('pageshow', () => {
            document.querySelectorAll('button[aria-busy="true"]').forEach((b) => { b.disabled = false; b.removeAttribute('aria-busy'); });
        });
    </script>
</body>
</html>
