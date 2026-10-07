@extends('layouts.admin')

@section('title', $order->order_number)
@section('heading', 'Order '.$order->order_number)

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-mono text-lg font-bold text-munch">{{ $order->order_number }}</p>
                    <p class="text-sm text-ink/55">{{ $order->created_at->format('d M Y H:i') }}</p>
                </div>
                <span class="badge bg-sage text-munch text-sm">{{ $order->status->label() }}</span>
            </div>

            <ul class="mt-6 space-y-4">
                @foreach($order->items as $item)
                    <li class="rounded-2xl bg-cream/70 p-4">
                        <div class="flex justify-between gap-3">
                            <p class="font-semibold">{{ $item->quantity }}× {{ $item->product_name }}</p>
                            <p class="font-semibold">{{ number_format($item->line_total, 0) }} RWF</p>
                        </div>
                        @if($item->options->isNotEmpty())
                            <p class="mt-2 text-sm text-ink/55">{{ $item->options->map(fn($o) => $o->option_group_name.': '.$o->option_name)->join(' · ') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 space-y-1 border-t border-munch/10 pt-4 text-sm">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($order->subtotal, 0) }} RWF</span></div>
                <div class="flex justify-between"><span>Delivery</span><span>{{ number_format($order->delivery_fee, 0) }} RWF</span></div>
                <div class="flex justify-between text-base font-bold text-munch"><span>Total</span><span>{{ $order->formattedTotal() }}</span></div>
            </div>
        </div>

        <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm">
            <h2 class="font-display text-lg font-bold text-munch">Status history</h2>
            <ol class="mt-4 space-y-3">
                @foreach($order->statusHistories as $history)
                    <li class="flex gap-3 text-sm">
                        <span class="mt-1 h-2 w-2 rounded-full bg-box"></span>
                        <div>
                            <p class="font-semibold">{{ $history->to_status->label() }}</p>
                            <p class="text-xs text-ink/45">{{ $history->created_at?->format('d M H:i') }} @if($history->user)· {{ $history->user->name }}@endif</p>
                            @if($history->notes)<p class="text-ink/60">{{ $history->notes }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="space-y-5">
        <div class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm text-sm">
            <h2 class="font-display text-lg font-bold text-munch">Customer</h2>
            <p class="mt-3 font-semibold">{{ $order->customer_name }}</p>
            <p>{{ $order->customer_phone }}</p>
            <p>{{ $order->customer_email }}</p>
            <p class="mt-3">{{ $order->fulfillment_type->label() }}</p>
            @if($order->delivery)
                <p class="mt-2 text-ink/70">{{ $order->delivery->fullAddress() }}</p>
            @endif
            @if($order->notes)
                <p class="mt-3 rounded-xl bg-cream p-3 text-ink/70">{{ $order->notes }}</p>
            @endif
        </div>

        @if(count($transitions))
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm space-y-3">
                @csrf
                @method('PATCH')
                <h2 class="font-display text-lg font-bold text-munch">Update status</h2>
                <select name="status" class="input-field" required>
                    @foreach($transitions as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
                @if($order->isDelivery())
                    <input name="driver_name" class="input-field" placeholder="Driver name" value="{{ $order->delivery?->driver_name }}">
                    <input name="driver_phone" class="input-field" placeholder="Driver phone" value="{{ $order->delivery?->driver_phone }}">
                @endif
                <textarea name="notes" class="input-field" rows="2" placeholder="Internal note"></textarea>
                <textarea name="rejection_reason" class="input-field" rows="2" placeholder="Rejection reason (if rejecting)"></textarea>
                <button class="btn-primary w-full">Update</button>
            </form>
        @endif
    </div>
</div>
@endsection
