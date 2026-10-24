@extends('layouts.customer')

@section('title', 'Track order')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
    <h1 class="text-3xl font-extrabold text-munch sm:text-4xl">Track your order</h1>
    <p class="mt-1 text-ink/60">Enter your order number and the phone number you ordered with.</p>

    <form method="GET" action="{{ route('orders.track') }}" class="card-surface mt-6 grid gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div>
            <label for="order_number" class="label-field">Order number</label>
            <input id="order_number" name="order_number" value="{{ request('order_number') }}" class="input-field font-mono uppercase" placeholder="MB-260322-ABC12" required autocomplete="off">
        </div>
        <div>
            <label for="phone" class="label-field">Phone</label>
            <input id="phone" type="tel" name="phone" value="{{ request('phone') }}" class="input-field" placeholder="07XX XXX XXX" required inputmode="tel" autocomplete="tel">
        </div>
        <button type="submit" class="btn-primary w-full sm:w-auto">Track</button>
    </form>

    @if(request()->filled('order_number'))
        <div class="mt-6">
            @if($order)
                @include('customer.orders._details', ['order' => $order])
            @else
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                    We couldn’t find an order with that number and phone. Check both and try again.
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
