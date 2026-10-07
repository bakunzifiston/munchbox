@extends('layouts.customer')

@section('title', 'Track order')

@section('content')
<div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
    <h1 class="font-display text-4xl font-bold text-munch">Track order</h1>
    <p class="mt-2 text-ink/60">Enter your order number and phone to see live status.</p>

    <form method="GET" class="card-surface mt-8 space-y-4 p-6">
        <div>
            <label class="label-field">Order number</label>
            <input name="order_number" value="{{ request('order_number') }}" class="input-field" placeholder="MB-260322-ABC12" required>
        </div>
        <div>
            <label class="label-field">Phone</label>
            <input name="phone" value="{{ request('phone') }}" class="input-field" placeholder="+250..." required>
        </div>
        <button class="btn-primary w-full">Track</button>
    </form>

    @if(request()->filled('order_number'))
        @if($order)
            <div class="card-surface mt-6 p-6">
                <div class="flex items-center justify-between">
                    <p class="font-mono font-bold text-munch">{{ $order->order_number }}</p>
                    <span class="badge bg-sage text-munch">{{ $order->status->label() }}</span>
                </div>
                <p class="mt-2 text-sm text-ink/60">{{ $order->formattedTotal() }} · {{ $order->fulfillment_type->label() }}</p>
                <a href="{{ route('orders.show', ['order' => $order, 'phone' => request('phone')]) }}" class="btn-secondary mt-4">View details</a>
            </div>
        @else
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">No order found for that number and phone.</div>
        @endif
    @endif
</div>
@endsection
