@extends('layouts.customer')

@section('title', 'My orders')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <h1 class="font-display text-4xl font-bold text-munch">My orders</h1>

    <div class="mt-8 space-y-4">
        @forelse($orders as $order)
            <a href="{{ route('orders.show', $order) }}" class="card-surface block p-5 transition hover:-translate-y-0.5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm font-bold text-munch">{{ $order->order_number }}</p>
                        <p class="mt-1 text-sm text-ink/60">{{ $order->created_at->format('d M Y, H:i') }} · {{ $order->items->count() }} item(s)</p>
                    </div>
                    <div class="text-right">
                        <span class="badge bg-sage text-munch">{{ $order->status->customerLabel($order->fulfillment_type) }}</span>
                        <p class="mt-2 font-bold text-munch">{{ $order->formattedTotal() }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="card-surface p-10 text-center">
                <p class="font-display text-2xl text-munch">No orders yet</p>
                <a href="{{ route('home') }}" class="btn-primary mt-4">Start ordering</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection
