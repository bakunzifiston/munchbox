@extends('layouts.customer')

@section('title', 'Cart')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <h1 class="font-display text-4xl font-bold text-munch">Your cart</h1>

    @if($items->isEmpty())
        <div class="mt-10 card-surface p-12 text-center">
            <p class="font-display text-2xl text-munch">Cart is empty</p>
            <p class="mt-2 text-ink/60">Browse the menu and build your sandwich.</p>
            <a href="{{ route('menu.index') }}" class="btn-primary mt-6">Browse menu</a>
        </div>
    @else
        <div class="mt-8 space-y-4">
            @foreach($items as $item)
                <div class="card-surface p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="font-display text-xl font-semibold text-munch">{{ $item['name'] }}</h2>
                            @if(!empty($item['options']))
                                <ul class="mt-2 space-y-1 text-sm text-ink/60">
                                    @foreach($item['options'] as $option)
                                        <li>{{ $option['group'] }}: {{ $option['name'] }}
                                            @if($option['price_adjustment'] > 0)
                                                (+{{ number_format($option['price_adjustment'], 0) }} RWF)
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if(!empty($item['notes']))
                                <p class="mt-2 text-xs italic text-ink/50">Note: {{ $item['notes'] }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-munch">{{ number_format($item['line_total'], 0) }} RWF</p>
                            <form method="POST" action="{{ route('cart.update', $item['key']) }}" class="mt-2 flex items-center justify-end gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="20" class="input-field w-20">
                                <button class="btn-secondary !px-3 text-xs">Update</button>
                            </form>
                            <form method="POST" action="{{ route('cart.destroy', $item['key']) }}" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 card-surface p-6">
            <div class="flex items-center justify-between text-sm">
                <span class="text-ink/60">Subtotal</span>
                <span class="font-bold text-munch">{{ number_format($subtotal, 0) }} RWF</span>
            </div>
            <a href="{{ route('checkout.create') }}" class="btn-primary mt-6 w-full">Proceed to checkout</a>
        </div>
    @endif
</div>
@endsection
