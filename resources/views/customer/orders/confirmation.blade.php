@extends('layouts.customer')

@section('title', 'Order confirmed')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <div class="card-surface p-8 text-center sm:p-12">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-box">Success</p>
        <h1 class="mt-2 font-display text-4xl font-bold text-munch">Order placed</h1>
        <p class="mt-3 text-ink/65">Thanks {{ $order->customer_name }}! We’re preparing your MunchBox order.</p>
        <p class="mt-6 inline-flex rounded-full bg-sage px-4 py-2 font-mono text-sm font-bold text-munch">{{ $order->order_number }}</p>
        <p class="mt-4 text-2xl font-bold text-munch">{{ $order->formattedTotal() }}</p>
        <p class="mt-2 text-sm text-ink/60">Status: <span class="font-semibold">{{ $order->status->label() }}</span> · {{ $order->fulfillment_type->label() }}</p>

        <div class="mt-8 space-y-3 text-left">
            @foreach($order->items as $item)
                <div class="rounded-2xl bg-cream/80 px-4 py-3 text-sm">
                    <div class="flex justify-between font-semibold">
                        <span>{{ $item->quantity }}× {{ $item->product_name }}</span>
                        <span>{{ number_format($item->line_total, 0) }} RWF</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('orders.show', $order) }}" class="btn-primary">Track this order</a>
            <a href="{{ route('menu.index') }}" class="btn-secondary">Order more</a>
        </div>
    </div>
</div>
@endsection
