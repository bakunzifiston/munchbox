@extends('layouts.customer')

@section('title', 'Checkout')

@section('content')
<div
    class="mx-auto max-w-5xl px-4 py-10 sm:px-6"
    x-data="{
        fulfillment: '{{ old('fulfillment_type', 'delivery') }}',
        init() {
            try {
                const loc = JSON.parse(localStorage.getItem('munchbox_delivery_location') || 'null');
                if (!loc) return;
                const street = document.querySelector('[name=\"address[street]\"]');
                const city = document.querySelector('[name=\"address[city]\"]');
                const district = document.querySelector('[name=\"address[district]\"]');
                const landmark = document.querySelector('[name=\"address[landmark]\"]');
                if (street && !street.value) street.value = loc.street || loc.title || '';
                if (city && !city.value) city.value = loc.city || 'Kigali';
                if (district && !district.value) district.value = (loc.detail || '').split(',')[0]?.trim() || '';
                if (landmark && !landmark.value) landmark.value = loc.detail || '';
            } catch (e) {}
        }
    }"
>
    <h1 class="font-display text-4xl font-bold text-munch">Checkout</h1>
    <p class="mt-2 text-ink/60">Almost there — confirm your details and place the order.</p>

    <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid gap-6 lg:grid-cols-5">
        @csrf
        <div class="space-y-5 lg:col-span-3">
            <div class="card-surface p-5 space-y-4">
                <h2 class="font-display text-xl font-semibold text-munch">Contact</h2>
                <div>
                    <label class="label-field">Full name</label>
                    <input name="name" value="{{ old('name', $user?->name) }}" class="input-field" required>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-field">Phone</label>
                        <input name="phone" value="{{ old('phone', $user?->phone) }}" class="input-field" required placeholder="+250...">
                        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label-field">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="input-field">
                    </div>
                </div>
            </div>

            <div class="card-surface p-5 space-y-4">
                <h2 class="font-display text-xl font-semibold text-munch">Fulfillment</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($fulfillmentTypes as $type)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-munch/15 bg-cream/50 px-4 py-3">
                            <input type="radio" name="fulfillment_type" value="{{ $type->value }}" x-model="fulfillment" @checked(old('fulfillment_type', 'delivery') === $type->value)>
                            <span class="font-semibold text-munch">{{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>

                <div x-show="fulfillment === 'delivery'" x-cloak class="space-y-4 border-t border-munch/10 pt-4">
                    <button type="button" class="flex w-full items-center justify-between rounded-2xl bg-[#F5F5F5] px-4 py-3 text-left" @click="$dispatch('open-location-modal')">
                        <span>
                            <span class="block text-xs font-bold uppercase tracking-wide text-ink/45">Delivering to</span>
                            <span class="font-bold text-munch" data-location-label>Kigali City</span>
                            <span class="block text-sm text-ink/55" data-location-detail>Amahoro, Kigali</span>
                        </span>
                        <span class="text-sm font-bold text-box">Change</span>
                    </button>
                    @if($addresses->isNotEmpty())
                        <div>
                            <label class="label-field">Saved address</label>
                            <select name="address_id" class="input-field">
                                <option value="">Enter a new address</option>
                                @foreach($addresses as $address)
                                    <option value="{{ $address->id }}" @selected(old('address_id') == $address->id)>{{ $address->fullAddress() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="label-field">Street / location</label>
                        <input name="address[street]" value="{{ old('address.street') }}" class="input-field" placeholder="KG 11 Ave, Nyarutarama">
                        @error('address.street')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label-field">District</label>
                            <input name="address[district]" value="{{ old('address.district') }}" class="input-field" placeholder="Gasabo">
                        </div>
                        <div>
                            <label class="label-field">City</label>
                            <input name="address[city]" value="{{ old('address.city', 'Kigali') }}" class="input-field">
                        </div>
                    </div>
                    <div>
                        <label class="label-field">Landmark</label>
                        <input name="address[landmark]" value="{{ old('address.landmark') }}" class="input-field">
                    </div>
                </div>
            </div>

            <div class="card-surface p-5 space-y-4">
                <h2 class="font-display text-xl font-semibold text-munch">Payment</h2>
                <div class="space-y-2">
                    @foreach($paymentMethods as $method)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-munch/15 px-4 py-3">
                            <input type="radio" name="payment_method" value="{{ $method->value }}" @checked(old('payment_method', 'cash') === $method->value)>
                            <span class="text-sm font-semibold">{{ $method->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <div>
                    <label class="label-field">Order notes</label>
                    <textarea name="notes" class="input-field" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="card-surface sticky top-24 p-5">
                <h2 class="font-display text-xl font-semibold text-munch">Summary</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach($items as $item)
                        <li class="flex justify-between gap-3">
                            <span>{{ $item['quantity'] }}× {{ $item['name'] }}</span>
                            <span class="font-semibold">{{ number_format($item['line_total'], 0) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 space-y-2 border-t border-munch/10 pt-4 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($subtotal, 0) }} RWF</span></div>
                    <div class="flex justify-between" x-show="fulfillment === 'delivery'">
                        <span>Delivery</span><span>{{ number_format($deliveryFee, 0) }} RWF</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-munch">
                        <span>Total</span>
                        <span x-text="fulfillment === 'delivery' ? '{{ number_format($subtotal + $deliveryFee, 0) }} RWF' : '{{ number_format($subtotal, 0) }} RWF'"></span>
                    </div>
                </div>
                <button class="btn-primary mt-6 w-full">Place order</button>
            </div>
        </div>
    </form>
</div>
@endsection
