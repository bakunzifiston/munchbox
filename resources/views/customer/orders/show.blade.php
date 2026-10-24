@extends('layouts.customer')

@section('title', 'Order '.$order->order_number)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
    @auth
        <a href="{{ route('orders.index') }}" class="inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-munch/70 hover:text-munch">
            <span aria-hidden="true">←</span> My orders
        </a>
    @endauth

    <div class="mt-1">
        @include('customer.orders._details', ['order' => $order])
    </div>

    @if($order->status === \App\Enums\OrderStatus::Pending && auth()->check() && $order->user_id === auth()->id())
        <form method="POST" action="{{ route('orders.cancel', $order) }}" class="mt-5" onsubmit="return confirm('Cancel this order?')">
            @csrf
            <button type="submit" class="btn-secondary text-red-700">Cancel order</button>
        </form>
    @endif
</div>
@endsection
