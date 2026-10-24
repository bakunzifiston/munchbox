@extends('layouts.customer')

@section('title', $product->name)

@section('content')
@php
    $base = (float) $product->base_price;
    $initialExtras = collect($selectedIds)->sum(fn ($id) => (float) ($charges[$id] ?? 0));
    $initialQty = max(1, min(\App\Services\CartService::MAX_QUANTITY, (int) old('quantity', 1)));
@endphp

<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
    <a href="{{ route('home') }}#{{ $product->category->slug }}" class="inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-munch/70 hover:text-munch">
        <span aria-hidden="true">←</span> Back to menu
    </a>

    <div class="mt-2 grid items-start gap-6 lg:grid-cols-2 lg:gap-10">
        <div class="lg:sticky lg:top-24">
            <x-product-image :image="$product->image" :name="$product->name" :lazy="false" class="aspect-[4/3] rounded-3xl" />
            <div class="mt-5">
                <p class="text-xs font-bold uppercase tracking-wider text-box">{{ $product->category->name }}</p>
                <div class="mt-1 flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-extrabold leading-tight text-munch sm:text-3xl">{{ $product->name }}</h1>
                    <p class="shrink-0 pt-1 text-xl font-extrabold text-box">{{ $product->formattedPrice() }}</p>
                </div>
                @if($product->description)
                    <p class="mt-3 leading-relaxed text-ink/65">{{ $product->description }}</p>
                @endif
                @if($servedFreshOrToasted)
                    <p class="mt-2 text-sm font-semibold text-munch/60">Served fresh or toasted</p>
                @endif
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('cart.store', $product) }}"
            class="space-y-4"
            x-data="productCustomizer({{ $base }}, {{ $initialQty }})"
            @change="recalc()"
        >
            @csrf

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($product->is_customizable && $product->optionGroups->isNotEmpty())
                <div>
                    <h2 class="text-xl font-extrabold text-munch">Make it yours</h2>
                    <p class="mt-1 text-sm text-ink/55">Ticked items come with this sandwich. Removing an ingredient doesn’t change the price; extras show their price.</p>
                </div>

                @foreach($product->optionGroups as $group)
                    @php
                        $single = $group->selection_type === \App\Enums\SelectionType::Single;
                        $max = $single ? 1 : max(1, $group->max_selections);
                    @endphp
                    <fieldset class="card-surface p-4 sm:p-5" data-group data-max="{{ $max }}" data-single="{{ $single ? 1 : 0 }}">
                        <legend class="sr-only">{{ $group->name }}</legend>
                        <div class="mb-3 flex items-baseline justify-between gap-3">
                            <h3 class="text-base font-extrabold text-munch">{{ $group->name }}</h3>
                            <p class="shrink-0 text-xs font-semibold text-ink/50">
                                {{ $single ? 'Choose one' : 'Up to '.$max }}{{ $group->is_required ? ' · Required' : '' }}
                            </p>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($group->activeOptions as $option)
                                @php
                                    $charge = (float) ($charges[$option->id] ?? 0);
                                    $included = in_array($option->id, $includedIds, true);
                                @endphp
                                <label class="option-tile">
                                    <span class="flex min-w-0 items-center gap-3">
                                        <input
                                            type="{{ $single ? 'radio' : 'checkbox' }}"
                                            name="{{ $single ? 'options['.$group->id.']' : 'options['.$group->id.'][]' }}"
                                            value="{{ $option->id }}"
                                            data-charge="{{ $charge }}"
                                            class="{{ $single ? 'rounded-full' : 'rounded' }} border-munch/30 text-box focus:ring-box"
                                            @checked(in_array($option->id, $selectedIds, true))
                                            @if($single && $group->is_required) required @endif
                                        >
                                        <span class="truncate text-sm font-medium text-munch">{{ $option->name }}</span>
                                    </span>
                                    @if($charge > 0)
                                        <span class="shrink-0 text-xs font-bold text-box">+{{ \App\Support\Money::format($charge) }}</span>
                                    @elseif($included)
                                        <span class="shrink-0 text-xs font-semibold text-ink/45">Included</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            @endif

            <div class="card-surface p-4 sm:p-5">
                <label for="notes" class="label-field">Special instructions <span class="font-normal normal-case text-ink/40">(optional)</span></label>
                <textarea id="notes" name="notes" rows="2" maxlength="500" class="input-field" placeholder="e.g. cut in half, light sauce">{{ old('notes') }}</textarea>
            </div>

            <div class="sticky bottom-3 z-10 rounded-3xl border border-munch/10 bg-white p-3 shadow-menu sm:p-4">
                <div class="flex items-center gap-3">
                    <div class="qty-stepper shrink-0">
                        <button type="button" @click="setQty(qty - 1)" :disabled="qty <= 1" aria-label="Decrease quantity">−</button>
                        <input type="number" name="quantity" min="1" max="{{ \App\Services\CartService::MAX_QUANTITY }}" value="{{ $initialQty }}" x-model.number="qty" @change="setQty(qty)" aria-label="Quantity">
                        <button type="button" @click="setQty(qty + 1)" :disabled="qty >= {{ \App\Services\CartService::MAX_QUANTITY }}" aria-label="Increase quantity">+</button>
                    </div>
                    <button type="submit" class="btn-primary min-h-[48px] flex-1 whitespace-nowrap !px-4 !text-base">
                        <span class="sm:hidden">Add</span>
                        <span class="hidden sm:inline">Add to cart</span>
                        <span aria-hidden="true">·</span>
                        <span x-text="format(total)">{{ \App\Support\Money::format(($base + $initialExtras) * $initialQty) }}</span>
                    </button>
                </div>
                <p class="mt-2 px-1 text-xs text-ink/50" x-show="extras > 0" x-cloak>
                    Includes extras of <span x-text="format(extras)"></span> per item.
                </p>
            </div>
        </form>
    </div>

    @if($related->isNotEmpty())
        <section class="mt-14">
            <h2 class="mb-4 text-xl font-extrabold text-munch">You may also like</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach($related as $item)
                    <a href="{{ route('menu.show', $item->slug) }}" class="menu-card group">
                        <x-product-image :image="$item->image" :name="$item->name" class="aspect-[16/10]" />
                        <div class="flex items-start justify-between gap-3 p-4">
                            <p class="font-bold text-munch">{{ $item->name }}</p>
                            <p class="shrink-0 text-sm font-bold text-box">{{ $item->formattedPrice() }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

<script>
function productCustomizer(base, qty) {
    return {
        base: Number(base),
        qty: Number(qty) || 1,
        extras: 0,
        init() {
            this.recalc();
        },
        setQty(value) {
            this.qty = Math.min({{ \App\Services\CartService::MAX_QUANTITY }}, Math.max(1, Math.round(Number(value) || 1)));
        },
        recalc() {
            let extras = 0;
            this.$el.querySelectorAll('[data-group]').forEach((group) => {
                const inputs = [...group.querySelectorAll('input[data-charge]')];
                const checked = inputs.filter((el) => el.checked);
                checked.forEach((el) => { extras += Number(el.dataset.charge || 0); });

                // Enforce "up to N" for multi-choice groups.
                if (group.dataset.single !== '1') {
                    const full = checked.length >= Number(group.dataset.max || Infinity);
                    inputs.forEach((el) => { el.disabled = full && !el.checked; });
                }
            });
            this.extras = extras;
        },
        get total() {
            return (this.base + this.extras) * this.qty;
        },
        format(n) {
            return new Intl.NumberFormat('en-US').format(Math.round(Number(n) || 0)) + ' RWF';
        },
    };
}
</script>
@endsection
