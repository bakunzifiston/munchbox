<x-guest-layout>
    <h1 class="mb-6 font-display text-2xl font-bold text-munch">Welcome back</h1>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full input-field" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full input-field" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-munch shadow-sm focus:ring-box" name="remember">
                <span class="ms-2 text-sm text-ink/60">Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-box" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>
        <button class="btn-primary w-full">Log in</button>
        <p class="text-center text-sm text-ink/60">No account? <a href="{{ route('register') }}" class="font-semibold text-box">Register</a></p>
    </form>
</x-guest-layout>
