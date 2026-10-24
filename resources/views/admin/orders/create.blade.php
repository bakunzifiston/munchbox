@extends('layouts.admin')

@section('title', 'New order')
@section('heading', 'New order')
@section('subheading', 'Enter a phone or walk-in order · items use their standard recipe')

@php
    $catalog = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'price' => (float) $p->base_price,
        'styles' => $styles[$p->id] ?? [],
    ])->values();
    $initialLines = old('lines', [['product_id' => '', 'quantity' => 1, 'style' => '', 'notes' => '']]);
@endphp

@section('content')
<form
    method="POST"
    action="{{ route('admin.orders.store') }}"
    class="grid items-start gap-4 xl:grid-cols-3"
    x-data="staffOrder({ catalog: @js($catalog), lines: @js(array_values($initialLines)), fulfillment: @js(old('fulfillment_type', 'pickup')), deliveryFee: {{ $deliveryFee }} })"
>
    @csrf
    <div class="space-y-4 xl:col-span-2">
        <x-admin.panel title="Items">
            <div class="space-y-3">
                <template x-for="(line, index) in lines" :key="index">
                    <div class="grid gap-2 rounded-xl border border-munch/10 p-3 sm:grid-cols-[2fr_90px_1fr_auto] sm:items-end">
                        <div>
                            <label class="label-field" :for="'product-' + index">Product</label>
                            <select class="input-field" :id="'product-' + index" :name="'lines[' + index + '][product_id]'" x-model.number="line.product_id" required>
                                <option value="">Choose…</option>
                                <template x-for="product in catalog" :key="product.id">
                                    <option :value="product.id" x-text="product.name + ' — ' + format(product.price)" :selected="product.id == line.product_id"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="label-field" :for="'qty-' + index">Qty</label>
                            <input type="number" min="1" max="{{ \App\Services\CartService::MAX_QUANTITY }}" class="input-field" :id="'qty-' + index" :name="'lines[' + index + '][quantity]'" x-model.number="line.quantity" required>
                        </div>
                        <div>
                            <template x-if="stylesFor(line).length">
                                <div>
                                    <label class="label-field" :for="'style-' + index">Style</label>
                                    <select class="input-field" :id="'style-' + index" :name="'lines[' + index + '][style]'" x-model="line.style">
                                        <template x-for="style in stylesFor(line)" :key="style">
                                            <option :value="style" x-text="style" :selected="style === line.style"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="btn-ghost text-rose-700" @click="lines.splice(index, 1)" x-show="lines.length > 1" aria-label="Remove item">Remove</button>
                        <div class="sm:col-span-4">
                            <input class="input-field" :name="'lines[' + index + '][notes]'" x-model="line.notes" maxlength="500" placeholder="Note for the kitchen (optional)" aria-label="Item note">
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" class="btn-secondary mt-3" @click="lines.push({ product_id: '', quantity: 1, style: '', notes: '' })">+ Add item</button>
            <p class="mt-3 text-xs text-ink/50">Need extras or ingredient changes? Add a note, or place the order through the storefront for full customization.</p>
        </x-admin.panel>

        <x-admin.panel title="Customer">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="name" class="label-field">Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" class="input-field" required maxlength="255">
                </div>
                <div>
                    <label for="phone" class="label-field">Phone</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="input-field" required minlength="9" maxlength="30" placeholder="07XX XXX XXX">
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
                @foreach(\App\Enums\FulfillmentType::cases() as $type)
                    <label class="option-tile justify-start">
                        <input type="radio" name="fulfillment_type" value="{{ $type->value }}" x-model="fulfillment" class="border-munch/30 text-box focus:ring-box">
                        <span class="text-sm font-bold text-munch">{{ $type->label() }}</span>
                    </label>
                @endforeach
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-3" x-show="fulfillment === 'delivery'" x-cloak>
                <div>
                    <label for="delivery_area" class="label-field">Area</label>
                    <select id="delivery_area" name="delivery_area" class="input-field" :required="fulfillment === 'delivery'">
                        <option value="">Choose…</option>
                        @foreach($deliveryAreas as $area)
                            <option value="{{ $area['title'] }}" @selected(old('delivery_area') === $area['title'])>{{ $area['title'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="street" class="label-field">Street / house</label>
                    <input id="street" name="address[street]" value="{{ old('address.street') }}" class="input-field" maxlength="255">
                </div>
                <div>
                    <label for="landmark" class="label-field">Landmark</label>
                    <input id="landmark" name="address[landmark]" value="{{ old('address.landmark') }}" class="input-field" maxlength="255">
                </div>
            </div>
        </x-admin.panel>
    </div>

    <x-admin.panel title="Summary" class="xl:sticky xl:top-20">
        <div>
            <label for="payment_method" class="label-field">Payment method</label>
            <select id="payment_method" name="payment_method" class="input-field">
                @foreach($paymentMethods as $method)
                    <option value="{{ $method->value }}" @selected(old('payment_method', 'cash') === $method->value)>{{ $method->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-3">
            <label for="notes" class="label-field">Order note</label>
            <textarea id="notes" name="notes" rows="2" maxlength="1000" class="input-field">{{ old('notes') }}</textarea>
        </div>
        <dl class="mt-4 space-y-1 border-t border-munch/10 pt-3 text-sm">
            <div class="flex justify-between"><dt class="text-ink/60">Items</dt><dd x-text="format(subtotal)"></dd></div>
            <div class="flex justify-between" x-show="fulfillment === 'delivery'"><dt class="text-ink/60">Delivery</dt><dd x-text="format(deliveryFee)"></dd></div>
            <div class="flex justify-between text-base font-extrabold text-munch"><dt>Estimated total</dt><dd x-text="format(total)"></dd></div>
        </dl>
        <p class="mt-1 text-xs text-ink/45">The final total is calculated from current menu prices when you create the order.</p>
        <button type="submit" class="btn-primary mt-4 w-full">Create order</button>
    </x-admin.panel>
</form>

<script>
function staffOrder({ catalog, lines, fulfillment, deliveryFee }) {
    return {
        catalog,
        lines: lines.map((l) => ({ product_id: l.product_id ? Number(l.product_id) : '', quantity: Number(l.quantity) || 1, style: l.style || '', notes: l.notes || '' })),
        fulfillment,
        deliveryFee: Number(deliveryFee) || 0,
        product(line) { return this.catalog.find((p) => p.id === Number(line.product_id)); },
        stylesFor(line) {
            const styles = this.product(line)?.styles || [];
            if (styles.length && !styles.includes(line.style)) line.style = styles[0];
            return styles;
        },
        get subtotal() {
            return this.lines.reduce((sum, line) => sum + (this.product(line)?.price || 0) * (Number(line.quantity) || 0), 0);
        },
        get total() { return this.subtotal + (this.fulfillment === 'delivery' ? this.deliveryFee : 0); },
        format(n) { return new Intl.NumberFormat('en-US').format(Math.round(Number(n) || 0)) + ' RWF'; },
    };
}
</script>
@endsection
