@extends('layouts.customer')

@section('title', 'Order '.$order->order_number)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-box">Order</p>
            <h1 class="font-display text-3xl font-bold text-munch">{{ $order->order_number }}</h1>
            <p class="mt-1 text-sm text-ink/60">{{ $order->created_at->format('d M Y, H:i') }}</p>
        </div>
        <span class="badge bg-sage text-munch">{{ $order->status->label() }}</span>
    </div>

    <div class="mt-8 card-surface p-5">
        <h2 class="font-semibold text-munch">Progress</h2>
        @php
            $steps = [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Confirmed, \App\Enums\OrderStatus::Preparing, \App\Enums\OrderStatus::Ready, \App\Enums\OrderStatus::OutForDelivery, \App\Enums\OrderStatus::Delivered];
            if ($order->fulfillment_type->value === 'pickup') {
                $steps = [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Confirmed, \App\Enums\OrderStatus::Preparing, \App\Enums\OrderStatus::Ready, \App\Enums\OrderStatus::Delivered];
            }
            $currentIndex = collect($steps)->search(fn ($s) => $s === $order->status);
            if ($currentIndex === false) $currentIndex = -1;
        @endphp
        <ol class="mt-4 space-y-3">
            @foreach($steps as $i => $step)
                <li class="flex items-center gap-3 text-sm {{ $i <= $currentIndex ? 'text-munch font-semibold' : 'text-ink/40' }}">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $i <= $currentIndex ? 'bg-munch text-white' : 'bg-sand text-ink/40' }}">{{ $i + 1 }}</span>
                    {{ $step->label() }}
                </li>
            @endforeach
        </ol>
        @if(in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Rejected]))
            <p class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $order->status->label() }}{{ $order->rejection_reason ? ': '.$order->rejection_reason : '' }}</p>
        @endif
    </div>

    <div class="mt-5 card-surface p-5">
        <h2 class="font-semibold text-munch">Items</h2>
        <ul class="mt-3 space-y-3">
            @foreach($order->items as $item)
                <li class="text-sm">
                    <div class="flex justify-between font-medium">
                        <span>{{ $item->quantity }}× {{ $item->product_name }}</span>
                        <span>{{ number_format($item->line_total, 0) }} RWF</span>
                    </div>
                    @if($item->options->isNotEmpty())
                        <p class="mt-1 text-ink/55">{{ $item->options->pluck('option_name')->join(', ') }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4 flex justify-between border-t border-munch/10 pt-4 font-bold text-munch">
            <span>Total</span>
            <span>{{ $order->formattedTotal() }}</span>
        </div>
    </div>

    @if($order->delivery)
        <div class="mt-5 card-surface p-5 text-sm">
            <h2 class="font-semibold text-munch">Delivery</h2>
            <p class="mt-2 text-ink/70">{{ $order->delivery->fullAddress() }}</p>
            @if($order->delivery->driver_name)
                <p class="mt-2">Driver: {{ $order->delivery->driver_name }} · {{ $order->delivery->driver_phone }}</p>
            @endif
        </div>
    @endif

    @if($order->status === \App\Enums\OrderStatus::Pending && auth()->check())
        <form method="POST" action="{{ route('orders.cancel', $order) }}" class="mt-6" onsubmit="return confirm('Cancel this order?')">
            @csrf
            <button class="btn-secondary text-red-700">Cancel order</button>
        </form>
    @endif
</div>
@endsection
