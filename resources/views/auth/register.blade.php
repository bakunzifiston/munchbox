<x-guest-layout>
    <h1 class="mb-6 font-display text-2xl font-bold text-munch">Create account</h1>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full input-field" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone" :value="__('Phone number')" />
            <x-text-input id="phone" class="block mt-1 w-full input-field" type="tel" name="phone" :value="old('phone')" required minlength="9" maxlength="30" autocomplete="tel" placeholder="+250 7XX XXX XXX" />
            <p class="mt-1 text-xs text-ink/45">Required — we’ll use this to confirm your orders.</p>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full input-field" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full input-field" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full input-field" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>
        <div class="flex items-center justify-between pt-2">
            <a class="text-sm font-semibold text-box" href="{{ route('login') }}">Already registered?</a>
            <button class="btn-primary">Register</button>
        </div>
    </form>
</x-guest-layout>
