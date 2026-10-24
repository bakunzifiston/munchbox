@extends('layouts.customer')

@section('title', 'Order placed')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
    <div class="card-surface p-6 text-center sm:p-10">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sage text-munch">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" /></svg>
        </div>
        <h1 class="mt-4 text-3xl font-extrabold text-munch">Thanks, {{ $order->customer_name }}!</h1>
        <p class="mt-2 text-ink/65">Your order is in. We’ll call {{ $order->customer_phone }} if we need anything.</p>
        <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-ink/45">Your order number</p>
        <p class="mt-1 inline-flex rounded-full bg-sage px-4 py-2 font-mono text-base font-bold text-munch">{{ $order->order_number }}</p>
        <p class="mt-2 text-sm text-ink/55">Keep it to track your order with your phone number.</p>

        <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
            <a href="{{ route('orders.track', ['order_number' => $order->order_number, 'phone' => $order->customer_phone]) }}" class="btn-primary">Track this order</a>
            <a href="{{ route('home') }}" class="btn-secondary">Back to menu</a>
        </div>
    </div>

    <div class="mt-4">
        @include('customer.orders._details', ['order' => $order])
    </div>
</div>
@endsection
