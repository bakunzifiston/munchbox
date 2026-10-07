@extends('layouts.customer')

@section('title', 'Account')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <h1 class="font-display text-4xl font-bold text-munch">Account</h1>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('account.update') }}" class="card-surface space-y-4 p-5">
            @csrf
            @method('PATCH')
            <h2 class="font-display text-xl font-semibold text-munch">Profile</h2>
            <div>
                <label class="label-field">Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" class="input-field" required>
            </div>
            <div>
                <label class="label-field">Phone</label>
                <input name="phone" value="{{ old('phone', $user->phone) }}" class="input-field" required>
            </div>
            <div>
                <label class="label-field">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input-field" required>
            </div>
            <button class="btn-primary">Save profile</button>
        </form>

        <div class="card-surface p-5">
            <h2 class="font-display text-xl font-semibold text-munch">Addresses</h2>
            <ul class="mt-4 space-y-3">
                @forelse($addresses as $address)
                    <li class="rounded-2xl bg-cream/80 px-4 py-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $address->label }}</p>
                                <p class="text-ink/60">{{ $address->fullAddress() }}</p>
                            </div>
                            <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-red-700">Remove</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-ink/50">No saved addresses.</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('account.addresses.store') }}" class="mt-6 space-y-3 border-t border-munch/10 pt-4">
                @csrf
                <p class="text-sm font-semibold text-munch">Add address</p>
                <input name="label" class="input-field" placeholder="Label (Home, Office)" required>
                <input name="street" class="input-field" placeholder="Street" required>
                <input name="district" class="input-field" placeholder="District">
                <input name="landmark" class="input-field" placeholder="Landmark">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> Default address</label>
                <button class="btn-secondary">Save address</button>
            </form>
        </div>
    </div>

    <div class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display text-2xl font-bold text-munch">Recent orders</h2>
            <a href="{{ route('orders.index') }}" class="btn-ghost">View all</a>
        </div>
        <div class="space-y-3">
            @forelse($orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="card-surface flex items-center justify-between p-4 text-sm">
                    <span class="font-mono font-bold">{{ $order->order_number }}</span>
                    <span>{{ $order->status->label() }} · {{ $order->formattedTotal() }}</span>
                </a>
            @empty
                <p class="text-ink/50">No orders yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
