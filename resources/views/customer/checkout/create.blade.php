@extends('layouts.customer')

@section('title', 'Checkout')

@section('content')
@php
    $fulfillment = old('fulfillment_type', 'delivery');
    $deliveryTotal = number_format($subtotal + $deliveryFee, 0).' RWF';
    $pickupTotal = number_format($subtotal, 0).' RWF';
@endphp

<div
    class="mx-auto max-w-5xl px-4 py-10 sm:px-6"
    x-data="{
        fulfillment: @js($fulfillment),
        deliveryTotal: @js($deliveryTotal),
        pickupTotal: @js($pickupTotal),
        get totalLabel() {
            return this.fulfillment === 'delivery' ? this.deliveryTotal : this.pickupTotal;
        },
        applyLocation(loc) {
            if (!loc) return;
            const street = this.$refs.street;
            const city = this.$refs.city;
            const district = this.$refs.district;
            const landmark = this.$refs.landmark;
            if (street && !street.value.trim()) {
                street.value = loc.street || loc.title || '';
            }
            if (city && !city.value.trim()) city.value = loc.city || 'Kigali';
            if (district && !district.value.trim()) {
                district.value = (loc.detail || '').split(',')[0]?.trim() || '';
            }
            if (landmark && !landmark.value.trim()) {
                landmark.value = loc.detail || '';
            }
            const label = document.querySelector('[data-checkout-location-label]');
            const detail = document.querySelector('[data-checkout-location-detail]');
            if (label) label.textContent = loc.street ? `${loc.title} · ${loc.street}` : (loc.title || 'Kigali City');
            if (detail) detail.textContent = loc.detail || 'Kigali';
        },
        init() {
            try {
                const loc = JSON.parse(localStorage.getItem('munchbox_delivery_location') || 'null');
                this.applyLocation(loc || { title: 'Kigali City', detail: 'Amahoro, Kigali', city: 'Kigali' });
            } catch (e) {
                this.applyLocation({ title: 'Kigali City', detail: 'Amahoro, Kigali', city: 'Kigali' });
            }
            window.addEventListener('location-updated', (e) => this.applyLocation(e.detail));
        }
    }"
>
    <h1 class="font-display text-4xl font-bold text-munch">Checkout</h1>
    <p class="mt-2 text-ink/60">Almost there — confirm your details and place the order.</p>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Please fix the following before placing your order:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" class="checkout-form mt-8 grid gap-6 lg:grid-cols-5" id="checkout-form">
        @csrf
        <div class="space-y-5 lg:col-span-3">
            <div class="card-surface space-y-4 p-5">
                <h2 class="font-display text-xl font-semibold text-munch">Contact</h2>
                <div>
                    <label class="label-field">Full name <span class="text-red-600">*</span></label>
                    <input name="name" value="{{ old('name', $user?->name) }}" class="input-field" required>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-field">Phone number <span class="text-red-600">*</span></label>
                        <input
                            type="tel"
                            name="phone"
                            value="{{ old('phone', $user?->phone) }}"
                            class="input-field"
                            required
                            minlength="9"
                            maxlength="30"
                            autocomplete="tel"
                            placeholder="+250 7XX XXX XXX"
                        >
                        <p class="mt-1 text-xs text-ink/45">Required so we can confirm and deliver your order.</p>
                        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label-field">Email <span class="font-normal text-ink/40">(optional)</span></label>
                        <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="input-field">
                    </div>
                </div>
            </div>

            <div class="card-surface space-y-4 p-5">
                <h2 class="font-display text-xl font-semibold text-munch">Fulfillment</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($fulfillmentTypes as $type)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-munch/15 bg-cream/50 px-4 py-3 has-[:checked]:border-box has-[:checked]:bg-box/5">
                            <input
                                type="radio"
                                name="fulfillment_type"
                                value="{{ $type->value }}"
                                x-model="fulfillment"
                                @checked($fulfillment === $type->value)
                                required
                            >
                            <span class="font-semibold text-munch">{{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="delivery-fields space-y-4 border-t border-munch/10 pt-4" x-show="fulfillment === 'delivery'" x-bind:style="fulfillment === 'delivery' ? '' : 'display:none'">
                    <button type="button" class="flex w-full items-center justify-between rounded-2xl bg-[#F5F5F5] px-4 py-3 text-left" @click="$dispatch('open-location-modal')">
                        <span>
                            <span class="block text-xs font-bold uppercase tracking-wide text-ink/45">Delivering to</span>
                            <span class="font-bold text-munch" data-checkout-location-label data-location-label>Kigali City</span>
                            <span class="block text-sm text-ink/55" data-checkout-location-detail data-location-detail>Amahoro, Kigali</span>
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
                        <label class="label-field">Street / location <span class="font-normal text-ink/40">(optional)</span></label>
                        <input
                            name="address[street]"
                            x-ref="street"
                            value="{{ old('address.street') }}"
                            class="input-field"
                            placeholder="KG 11 Ave, Nyarutarama"
                        >
                        <p class="mt-1 text-xs text-ink/45">Optional — add a street or landmark if you want more precise delivery.</p>
                        @error('address.street')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label-field">District</label>
                            <input name="address[district]" x-ref="district" value="{{ old('address.district') }}" class="input-field" placeholder="Gasabo">
                        </div>
                        <div>
                            <label class="label-field">City</label>
                            <input name="address[city]" x-ref="city" value="{{ old('address.city', 'Kigali') }}" class="input-field">
                        </div>
                    </div>
                    <div>
                        <label class="label-field">Landmark</label>
                        <input name="address[landmark]" x-ref="landmark" value="{{ old('address.landmark', 'Amahoro, Kigali') }}" class="input-field">
                    </div>
                </div>
            </div>

            <div class="card-surface space-y-4 p-5">
                <h2 class="font-display text-xl font-semibold text-munch">Payment</h2>
                <div class="space-y-2">
                    @foreach($paymentMethods as $method)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-munch/15 px-4 py-3 has-[:checked]:border-box has-[:checked]:bg-box/5">
                            <input
                                type="radio"
                                name="payment_method"
                                value="{{ $method->value }}"
                                @checked(old('payment_method', 'cash') === $method->value)
                                required
                            >
                            <span class="text-sm font-semibold">{{ $method->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('payment_method')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
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
                    <div class="flex justify-between" x-show="fulfillment === 'delivery'" x-bind:style="fulfillment === 'delivery' ? '' : 'display:none'">
                        <span>Delivery</span><span>{{ number_format($deliveryFee, 0) }} RWF</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-munch">
                        <span>Total</span>
                        <span x-text="totalLabel">{{ $fulfillment === 'delivery' ? $deliveryTotal : $pickupTotal }}</span>
                    </div>
                </div>
                <button type="submit" class="btn-primary mt-6 w-full">Place order</button>
                <p class="mt-3 text-center text-xs text-ink/45">By placing this order you confirm your phone number is correct.</p>
            </div>
        </div>
    </form>
</div>

<style>
    /* Keep delivery fields visible even if Alpine fails to load */
    .checkout-form:not([x-data]) .delivery-fields,
    .checkout-form .delivery-fields {
        display: block;
    }
    .checkout-form:has([name="fulfillment_type"][value="pickup"]:checked) .delivery-fields {
        display: none !important;
    }
</style>

@endsection
