<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MunchBox') }}</title>
    <link rel="icon" href="{{ asset('images/brand/app-icon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream text-ink">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="mb-6">
            <x-brand-mark size="lg" :show-tagline="true" />
        </a>
        <div class="w-full max-w-md rounded-3xl border border-munch/10 bg-white/90 p-6 shadow-[0_18px_50px_-28px_rgba(16,48,24,0.4)] sm:p-8">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
