@extends('layouts.customer')

@section('title', 'Checkout')

@section('content')
@php
    $fulfillment = old('fulfillment_type', 'delivery');
    $areaTitles = array_column($deliveryAreas, 'title');
@endphp

<div
    class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10"
    x-data="checkoutForm({
        fulfillment: @js($fulfillment),
        area: @js(old('delivery_area')),
        addressId: @js((string) old('address_id', '')),
        areas: @js($areaTitles),
        subtotal: {{ (float) $subtotal }},
        deliveryFee: {{ (float) $deliveryFee }},
    })"
>
    <a href="{{ route('cart.index') }}" class="inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-munch/70 hover:text-munch">
        <span aria-hidden="true">←</span> Back to cart
    </a>
    <h1 class="mt-1 text-3xl font-extrabold text-munch sm:text-4xl">Checkout</h1>

    @foreach($notices as $notice)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">{{ $notice }}</div>
    @endforeach

    @if ($errors->any())
        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-bold">Please check the following:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_360px]" @submit="submitting = true">
        @csrf
        <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

        <div class="space-y-4">
            <section class="card-surface space-y-4 p-5">
                <h2 class="text-lg font-extrabold text-munch">Your details</h2>
                <div>
                    <label for="name" class="label-field">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $user?->name) }}" class="input-field" required autocomplete="name">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="label-field">Phone number</label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone', $user?->phone) }}" class="input-field" required minlength="9" maxlength="30" autocomplete="tel" inputmode="tel" placeholder="07XX XXX XXX">
                        <p class="mt-1 text-xs text-ink/45">We’ll call this number to confirm and deliver.</p>
                        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="label-field">Email <span class="font-normal normal-case text-ink/40">(optional)</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" class="input-field" autocomplete="email">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="card-surface space-y-4 p-5">
                <h2 class="text-lg font-extrabold text-munch">Delivery or pickup</h2>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($fulfillmentTypes as $type)
                        <label class="option-tile justify-start">
                            <input type="radio" name="fulfillment_type" value="{{ $type->value }}" x-model="fulfillment" @checked($fulfillment === $type->value) required class="border-munch/30 text-box focus:ring-box">
                            <span class="text-sm font-bold text-munch">{{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="space-y-4 border-t border-munch/10 pt-4" x-show="fulfillment === 'delivery'" @if($fulfillment !== 'delivery') x-cloak @endif>
                    @if($addresses->isNotEmpty())
                        <div>
                            <label for="address_id" class="label-field">Saved address</label>
                            <select id="address_id" name="address_id" class="input-field" x-model="addressId">
                                <option value="">Use a new address</option>
                                @foreach($addresses as $address)
                                    <option value="{{ $address->id }}" @selected((string) old('address_id') === (string) $address->id)>{{ $address->fullAddress() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="space-y-4" x-show="!addressId">
                        <div>
                            <label for="delivery_area" class="label-field">Delivery area</label>
                            <select id="delivery_area" name="delivery_area" class="input-field" x-model="area" :required="fulfillment === 'delivery' && !addressId">
                                <option value="">Choose your area</option>
                                @foreach($deliveryAreas as $deliveryArea)
                                    <option value="{{ $deliveryArea['title'] }}" @selected(old('delivery_area') === $deliveryArea['title'])>{{ $deliveryArea['title'] }} — {{ $deliveryArea['detail'] }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-ink/45">Not listed? Choose pickup, or call us to check.</p>
                            @error('delivery_area')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="street" class="label-field">Street or house <span class="font-normal normal-case text-ink/40">(optional)</span></label>
                            <input id="street" name="address[street]" x-ref="street" value="{{ old('address.street') }}" class="input-field" placeholder="KG 11 Ave, house 24" autocomplete="street-address">
                            @error('address.street')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="landmark" class="label-field">Landmark <span class="font-normal normal-case text-ink/40">(optional)</span></label>
                            <input id="landmark" name="address[landmark]" value="{{ old('address.landmark') }}" class="input-field" placeholder="Near Amahoro Stadium, blue gate">
                        </div>
                    </div>
                </div>
            </section>

            <section class="card-surface space-y-4 p-5">
                <h2 class="text-lg font-extrabold text-munch">Payment</h2>
                <div class="grid gap-2">
                    @foreach($paymentMethods as $method)
                        <label class="option-tile justify-start">
                            <input type="radio" name="payment_method" value="{{ $method->value }}" @checked(old('payment_method', 'cash') === $method->value) required class="border-munch/30 text-box focus:ring-box">
                            <span class="text-sm font-semibold text-munch">{{ $method->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('payment_method')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div>
                    <label for="notes" class="label-field">Notes for us or the rider <span class="font-normal normal-case text-ink/40">(optional)</span></label>
                    <textarea id="notes" name="notes" class="input-field" rows="2" maxlength="1000" placeholder="Call when you arrive">{{ old('notes') }}</textarea>
                </div>
            </section>
        </div>

        <aside class="card-surface p-5 lg:sticky lg:top-24">
            <h2 class="text-lg font-extrabold text-munch">Your order</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach($items as $item)
                    @php $paidExtras = collect($item['options'] ?? [])->filter(fn ($o) => (float) $o['price_adjustment'] > 0); @endphp
                    <li>
                        <div class="flex justify-between gap-3">
                            <span class="font-semibold text-munch">{{ $item['quantity'] }}× {{ $item['name'] }}</span>
                            <span class="shrink-0 font-semibold">{{ \App\Support\Money::format($item['line_total']) }}</span>
                        </div>
                        @if(! empty($item['options']))
                            <p class="mt-0.5 text-xs text-ink/50">{{ collect($item['options'])->pluck('name')->join(', ') }}</p>
                        @endif
                        @if($paidExtras->isNotEmpty())
                            <p class="mt-0.5 text-xs text-box">Extras: {{ $paidExtras->map(fn ($o) => $o['name'].' +'.\App\Support\Money::format($o['price_adjustment']))->join(', ') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-2 border-t border-munch/10 pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-ink/60">Subtotal</dt><dd>{{ \App\Support\Money::format($subtotal) }}</dd></div>
                <div class="flex justify-between" x-show="fulfillment === 'delivery'" @if($fulfillment !== 'delivery') x-cloak @endif>
                    <dt class="text-ink/60">Delivery</dt><dd>{{ \App\Support\Money::format($deliveryFee) }}</dd>
                </div>
                <div class="flex justify-between pt-1 text-base font-extrabold text-munch">
                    <dt>Total</dt>
                    <dd x-text="format(total)">{{ \App\Support\Money::format($fulfillment === 'delivery' ? $subtotal + $deliveryFee : $subtotal) }}</dd>
                </div>
            </dl>
            <button type="submit" class="btn-primary mt-5 w-full !text-base" :disabled="submitting">
                <span x-show="!submitting">Place order</span>
                <span x-show="submitting" x-cloak>Placing your order…</span>
            </button>
            <p class="mt-3 text-center text-xs text-ink/45">Please make sure your phone number is correct.</p>
        </aside>
    </form>
</div>

<script>
function checkoutForm(config) {
    return {
        ...config,
        submitting: false,
        init() {
            // Pre-fill from the header's delivery area picker when the customer hasn't chosen one here.
            const apply = (loc) => {
                if (!loc) return;
                if (!this.area && this.areas.includes(loc.title)) this.area = loc.title;
                if (loc.street && this.$refs.street && !this.$refs.street.value.trim()) this.$refs.street.value = loc.street;
            };
            try { apply(JSON.parse(localStorage.getItem('munchbox_delivery_location') || 'null')); } catch (e) {}
            window.addEventListener('location-updated', (e) => {
                if (this.areas.includes(e.detail?.title)) this.area = e.detail.title;
                if (e.detail?.street && this.$refs.street) this.$refs.street.value = e.detail.street;
            });
            // Re-enable the button if the customer comes back with the browser's back button.
            window.addEventListener('pageshow', () => { this.submitting = false; });
        },
        get total() {
            return this.subtotal + (this.fulfillment === 'delivery' ? this.deliveryFee : 0);
        },
        format(n) {
            return new Intl.NumberFormat('en-US').format(Math.round(Number(n) || 0)) + ' RWF';
        },
    };
}
</script>
@endsection
