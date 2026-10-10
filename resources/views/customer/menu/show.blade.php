@extends('layouts.customer')

@section('title', $product->name)

@section('content')
@php
    $defaultPrices = collect($product->optionGroups)
        ->flatMap(fn ($g) => $g->activeOptions)
        ->whereIn('id', $defaultOptionIds)
        ->mapWithKeys(fn ($o) => [(string) $o->id => (float) $o->price_adjustment]);
@endphp

<div
    class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10"
    x-data="productCustomizer({{ $product->base_price }}, {{ Js::from($defaultPrices) }})"
>
    <a href="{{ route('menu.index') }}" class="btn-ghost mb-6 inline-flex px-0 text-munch/70 hover:text-munch">← Back to menu</a>

    <div class="grid items-start gap-8 lg:grid-cols-5 lg:gap-10">
        <aside class="space-y-4 lg:col-span-2 lg:sticky lg:top-24">
            <div class="card-surface overflow-hidden">
                <div class="aspect-[4/3] overflow-hidden bg-sand">
                    <img
                        src="{{ asset($product->image ?: 'images/sandwiches/classic.jpg') }}"
                        alt="{{ $product->name }}"
                        class="h-full w-full object-cover"
                    >
                </div>

                <div class="space-y-4 p-5 sm:p-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-box">{{ $product->category->name }}</p>
                        <h1 class="mt-1 text-2xl font-extrabold text-munch sm:text-3xl">{{ $product->name }}</h1>
                        <p class="mt-2 text-lg font-extrabold text-box">{{ $product->formattedPrice() }}</p>
                        <p class="mt-1 text-xs font-semibold text-ink/45">Served Fresh or Toasted</p>
                    </div>
                    <p class="text-sm leading-relaxed text-ink/65">{{ $product->description }}</p>

                    <div class="rounded-[22px] bg-sage/80 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-munch/55">Your build</p>
                            <p class="text-lg font-extrabold text-munch" x-text="format(total)"></p>
                        </div>

                        <ul class="mt-3 max-h-56 space-y-2 overflow-y-auto text-sm">
                            <template x-for="item in selectedSummary" :key="item.id">
                                <li class="flex items-start justify-between gap-3 border-b border-munch/5 pb-2 last:border-0">
                                    <div>
                                        <p class="font-medium text-munch" x-text="item.name"></p>
                                        <p class="text-xs text-ink/45" x-text="item.group"></p>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold text-ink/55" x-text="item.priceLabel"></span>
                                </li>
                            </template>
                            <li x-show="selectedSummary.length === 0" class="text-ink/40">Pick options to build your sandwich.</li>
                        </ul>

                        <div class="mt-4 space-y-1.5 border-t border-munch/10 pt-3 text-sm">
                            <div class="flex justify-between text-ink/60">
                                <span>Base</span>
                                <span x-text="format(base)"></span>
                            </div>
                            <div class="flex justify-between text-ink/60">
                                <span>Extras</span>
                                <span x-text="format(optionsTotal)"></span>
                            </div>
                            <div class="flex justify-between text-ink/60">
                                <span>Qty</span>
                                <span x-text="qty"></span>
                            </div>
                            <div class="flex justify-between text-base font-extrabold text-munch">
                                <span>Total</span>
                                <span x-text="format(total)"></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            @if($related->isNotEmpty())
                <div class="card-surface hidden p-5 lg:block">
                    <p class="text-sm font-extrabold text-munch">Also popular</p>
                    <ul class="mt-3 space-y-2">
                        @foreach($related as $item)
                            <li>
                                <a href="{{ route('menu.show', $item->slug) }}" class="flex items-center gap-3 rounded-2xl p-2 transition hover:bg-sage/70">
                                    <img src="{{ asset($item->image ?: 'images/sandwiches/classic.jpg') }}" alt="{{ $item->name }}" class="h-12 w-12 shrink-0 rounded-xl object-cover">
                                    <span>
                                        <span class="block text-sm font-semibold text-munch">{{ $item->name }}</span>
                                        <span class="text-xs font-bold text-box">{{ $item->formattedPrice() }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>

        <div class="lg:col-span-3">
            <div class="mb-6">
                <h2 class="text-2xl font-extrabold text-munch sm:text-3xl">Customize yours</h2>
                <p class="mt-2 text-ink/60">Build it your way — we’ll keep your live total on the side.</p>
            </div>

            <form method="POST" action="{{ route('cart.store', $product) }}" class="space-y-5" id="customize-form">
                @csrf

                @error('options')
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
                @enderror

                @if($product->is_customizable)
                    @foreach($product->optionGroups as $group)
                        <div class="card-surface p-5 sm:p-6">
                            <div class="mb-4">
                                <h2 class="font-display text-lg font-extrabold text-munch">{{ $group->name }}</h2>
                                <p class="mt-1 text-xs text-ink/50">
                                    {{ $group->selection_type->value === 'single' ? 'Choose one' : 'Choose up to '.$group->max_selections }}
                                    @if($group->is_required) · Required @endif
                                </p>
                            </div>
                            <div class="grid gap-2.5 sm:grid-cols-2">
                                @foreach($group->activeOptions as $option)
                                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-munch/10 bg-cream/80 px-4 py-3.5 transition hover:border-box/40 has-[:checked]:border-box has-[:checked]:bg-box/5 has-[:checked]:shadow-sm">
                                        <span class="flex items-center gap-3">
                                            <input
                                                type="{{ $group->selection_type->value === 'single' ? 'radio' : 'checkbox' }}"
                                                name="{{ $group->selection_type->value === 'single' ? 'options['.$group->id.']' : 'options['.$group->id.'][]' }}"
                                                value="{{ $option->id }}"
                                                class="rounded border-munch/30 text-box focus:ring-box"
                                                @if(in_array($option->id, $defaultOptionIds, true)) checked @endif
                                                @change="toggleOption({{ $option->id }}, {{ $option->price_adjustment }}, $event.target.checked, '{{ $group->selection_type->value }}', '{{ $group->id }}', '{{ addslashes($option->name) }}', '{{ addslashes($group->name) }}')"
                                                data-group="{{ $group->id }}"
                                                data-price="{{ $option->price_adjustment }}"
                                                data-name="{{ $option->name }}"
                                                data-group-name="{{ $group->name }}"
                                            >
                                            <span class="text-sm font-medium text-munch">{{ $option->name }}</span>
                                        </span>
                                        <span class="text-xs font-semibold text-ink/55">{{ $option->formattedPriceAdjustment() }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-surface p-5 text-sm text-ink/65">
                        This item is ready as listed — choose a quantity and add it to your cart.
                    </div>
                @endif

                <div class="card-surface sticky bottom-4 z-10 p-5 shadow-menu">
                    <label class="label-field">Special notes</label>
                    <textarea name="notes" rows="2" class="input-field" placeholder="No onions, extra crispy, etc."></textarea>
                    <div class="mt-4 flex flex-wrap items-end gap-4">
                        <div>
                            <label class="label-field">Qty</label>
                            <input type="number" name="quantity" min="1" max="20" value="1" class="input-field w-24" x-model.number="qty">
                        </div>
                        <button type="submit" class="btn-primary min-h-[48px] flex-1 !text-base">
                            Add to cart · <span x-text="format(total)"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($related->isNotEmpty())
        <section class="mt-14 lg:hidden">
            <h2 class="mb-5 font-display text-2xl font-extrabold text-munch">You may also like</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach($related as $item)
                    <a href="{{ route('menu.show', $item->slug) }}" class="menu-card overflow-hidden">
                        <div class="aspect-[16/10] overflow-hidden">
                            <img src="{{ asset($item->image ?: 'images/sandwiches/classic.jpg') }}" alt="{{ $item->name }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <div class="p-4">
                            <p class="font-semibold text-munch">{{ $item->name }}</p>
                            <p class="mt-1 text-sm font-bold text-box">{{ $item->formattedPrice() }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

<script>
function productCustomizer(base, initialPrices = {}) {
    return {
        base: Number(base),
        qty: 1,
        optionPrices: { ...initialPrices },
        optionMeta: {},
        init() {
            this.$el.querySelectorAll('input[name^="options["]').forEach((el) => {
                this.optionMeta[el.value] = {
                    name: el.dataset.name,
                    group: el.dataset.groupName,
                    price: Number(el.dataset.price || 0),
                };
            });
            this.$el.querySelectorAll('input[name^="options["]:checked').forEach((el) => {
                this.optionPrices[el.value] = Number(el.dataset.price || 0);
            });
        },
        toggleOption(id, price, checked, type, groupId, name, groupName) {
            this.optionMeta[id] = { name, group: groupName, price: Number(price) };
            if (type === 'single') {
                this.$el.querySelectorAll(`input[data-group="${groupId}"]`).forEach((el) => {
                    if (String(el.value) !== String(id)) delete this.optionPrices[el.value];
                });
            }
            if (checked) this.optionPrices[id] = Number(price);
            else delete this.optionPrices[id];
        },
        get optionsTotal() {
            return Object.values(this.optionPrices).reduce((a, b) => a + Number(b), 0);
        },
        get total() {
            return (this.base + this.optionsTotal) * this.qty;
        },
        get selectedSummary() {
            return Object.keys(this.optionPrices).map((id) => {
                const meta = this.optionMeta[id] || { name: 'Option', group: '', price: this.optionPrices[id] };
                const price = Number(this.optionPrices[id] || 0);
                return {
                    id,
                    name: meta.name,
                    group: meta.group,
                    priceLabel: price === 0 ? 'Included' : (price > 0 ? '+' : '') + this.format(price),
                };
            });
        },
        format(n) {
            return new Intl.NumberFormat('en-RW').format(Math.round(Number(n) || 0)) + ' RWF';
        }
    }
}
</script>
@endsection
