@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)
@section('heading', 'Order '.$order->order_number)
@section('subheading', 'Placed '.$order->created_at->format('d M Y, H:i').' · '.$order->created_at->diffForHumans())

@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $payment = $order->payments->sortByDesc('id')->first();
    $stopTransitions = array_filter($transitions, fn ($s) => in_array($s, [OrderStatus::Cancelled, OrderStatus::Rejected], true));
    $forwardTransitions = array_filter($transitions, fn ($s) => ! in_array($s, [OrderStatus::Cancelled, OrderStatus::Rejected], true));
    $finished = $order->status->isTerminal();
@endphp

@section('content')
<div class="mb-4 flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.orders.index') }}" class="btn-ghost !px-2 text-ink/60">← Orders</a>
    <x-admin.status-badge :status="$order->status" class="!text-xs" />
    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-munch ring-1 ring-munch/10">{{ $order->fulfillment_type->label() }}</span>
    <a href="{{ route('admin.orders.receipt', $order) }}" target="_blank" rel="noopener" class="btn-secondary ml-auto !min-h-[36px] !py-1.5 text-xs">Print receipt</a>
</div>

<div class="grid gap-4 xl:grid-cols-3">
    <div class="space-y-4 xl:col-span-2">
        {{-- Next step --}}
        <x-admin.panel title="Next step">
            @if($finished)
                <p class="text-sm text-ink/60">
                    This order is {{ strtolower($order->status->label()) }}{{ $order->rejection_reason ? ' — '.$order->rejection_reason : '' }}. No further changes are possible.
                </p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($forwardTransitions as $next)
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" data-confirm="{{ $next->actionLabel($order->fulfillment_type) }} for {{ $order->order_number }}?">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $next->value }}">
                            <button type="submit" class="{{ $loop->first ? 'btn-primary' : 'btn-secondary' }}">{{ $next->actionLabel($order->fulfillment_type) }}</button>
                        </form>
                    @endforeach
                </div>
                @if(in_array(OrderStatus::Preparing, $forwardTransitions, true) && \App\Models\BusinessSetting::getValue('inventory_deduct_on_status', 'preparing') === 'preparing')
                    <p class="mt-2 text-xs text-ink/50">Starting preparation takes the recipe ingredients out of stock.</p>
                @endif

                @if($stopTransitions)
                    <details class="mt-4 rounded-xl border border-rose-100 bg-rose-50/40 p-3">
                        <summary class="cursor-pointer text-sm font-bold text-rose-700">Cancel or reject this order</summary>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-3 space-y-3" data-confirm="This can’t be undone. Continue?">
                            @csrf @method('PATCH')
                            <div class="flex flex-wrap gap-3">
                                @foreach($stopTransitions as $stop)
                                    <label class="flex items-center gap-2 text-sm font-semibold">
                                        <input type="radio" name="status" value="{{ $stop->value }}" required @checked($loop->first)> {{ $stop->label() }}
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label for="reason" class="label-field">Reason (shown to the customer)</label>
                                <textarea id="reason" name="reason" rows="2" required maxlength="1000" class="input-field" placeholder="e.g. Customer asked to cancel; out of bread"></textarea>
                            </div>
                            <button type="submit" class="btn-secondary !border-rose-200 text-rose-700">Confirm</button>
                        </form>
                    </details>
                @endif
            @endif
        </x-admin.panel>

        {{-- Items --}}
        <x-admin.panel title="Items">
            <ul class="divide-y divide-munch/5">
                @foreach($order->items as $item)
                    <li class="py-3 first:pt-0">
                        <div class="flex justify-between gap-3">
                            <p class="font-bold text-munch">{{ $item->quantity }}× {{ $item->product_name }}</p>
                            <p class="shrink-0 font-bold text-munch">{{ Money::format($item->line_total) }}</p>
                        </div>
                        <p class="text-xs text-ink/50">{{ Money::format($item->unit_price) }} each{{ (float) $item->options_total > 0 ? ' + '.Money::format($item->options_total).' extras' : '' }}</p>
                        @if($item->options->isNotEmpty())
                            <dl class="mt-1.5 space-y-0.5 text-sm text-ink/65">
                                @foreach($item->options->groupBy('option_group_name') as $group => $options)
                                    <div>
                                        <dt class="inline font-semibold text-ink/45">{{ $group }}:</dt>
                                        <dd class="inline">{{ $options->map(fn ($o) => $o->option_name.((float) $o->price_adjustment > 0 ? ' (+'.Money::format($o->price_adjustment).')' : ''))->join(', ') }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                        @if($item->notes)
                            <p class="mt-1 rounded-lg bg-amber-50 px-2 py-1 text-sm text-amber-900">Note: {{ $item->notes }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
            <dl class="mt-3 space-y-1 border-t border-munch/10 pt-3 text-sm">
                <div class="flex justify-between"><dt class="text-ink/60">Subtotal</dt><dd>{{ Money::format($order->subtotal) }}</dd></div>
                @if((float) $order->delivery_fee > 0)
                    <div class="flex justify-between"><dt class="text-ink/60">Delivery fee</dt><dd>{{ Money::format($order->delivery_fee) }}</dd></div>
                @endif
                @if((float) $order->discount > 0)
                    <div class="flex justify-between"><dt class="text-ink/60">Discount</dt><dd>−{{ Money::format($order->discount) }}</dd></div>
                @endif
                <div class="flex justify-between text-base font-extrabold text-munch"><dt>Total</dt><dd>{{ Money::format($order->total) }}</dd></div>
            </dl>
            @if($order->notes)
                <p class="mt-3 rounded-lg bg-[#F6F4EF] px-3 py-2 text-sm"><span class="font-semibold">Order note:</span> {{ $order->notes }}</p>
            @endif
        </x-admin.panel>

        {{-- History --}}
        <x-admin.panel title="History">
            <ol class="space-y-3">
                @foreach($order->statusHistories as $history)
                    <li class="flex gap-3 text-sm">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-munch/40"></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-munch">
                                @if($history->from_status && $history->from_status !== $history->to_status)
                                    {{ $history->from_status->label() }} → {{ $history->to_status->label() }}
                                @elseif($history->from_status)
                                    Update
                                @else
                                    {{ $history->to_status->label() }}
                                @endif
                            </p>
                            @if($history->notes)<p class="text-ink/60">{{ $history->notes }}</p>@endif
                            <p class="text-xs text-ink/45">{{ $history->created_at?->format('d M Y, H:i') }}{{ $history->user ? ' · '.$history->user->name : '' }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-admin.panel>
    </div>

    <div class="space-y-4">
        <x-admin.panel title="Customer">
            <p class="font-bold text-munch">{{ $order->customer_name }}</p>
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}" class="text-sm font-semibold text-box">{{ $order->customer_phone }}</a>
            @if($order->customer_email)<p class="text-sm text-ink/60">{{ $order->customer_email }}</p>@endif
            @can('view-customers')
                @if($order->customer)
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="mt-2 inline-block text-xs font-bold text-box hover:underline">Customer history</a>
                @endif
            @endcan
        </x-admin.panel>

        <x-admin.panel title="Payment">
            @if($payment)
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-ink/60">Method</dt><dd class="font-semibold">{{ $payment->method->label() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink/60">Status</dt><dd class="font-semibold {{ $payment->status === PaymentStatus::Paid ? 'text-emerald-700' : 'text-amber-700' }}">{{ $payment->status->label() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink/60">Amount</dt><dd>{{ Money::format($payment->amount) }}</dd></div>
                    @if($payment->reference)<div class="flex justify-between"><dt class="text-ink/60">Reference</dt><dd>{{ $payment->reference }}</dd></div>@endif
                    @if($payment->paid_at)<div class="flex justify-between"><dt class="text-ink/60">Paid</dt><dd>{{ $payment->paid_at->format('d M, H:i') }}</dd></div>@endif
                </dl>
            @else
                <p class="text-sm text-ink/55">No payment recorded.</p>
            @endif

            @if(! $isPaid && ! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))
                <form method="POST" action="{{ route('admin.orders.payment', $order) }}" class="mt-4 space-y-2 border-t border-munch/10 pt-4" data-confirm="Record {{ Money::format($order->total) }} as received?">
                    @csrf @method('PATCH')
                    <div class="grid grid-cols-2 gap-2">
                        <select name="method" class="input-field" aria-label="Payment method">
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->value }}" @selected($payment?->method === $method)>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                        <input name="reference" maxlength="100" class="input-field" placeholder="Reference (optional)" aria-label="Payment reference">
                    </div>
                    <button type="submit" class="btn-secondary w-full">Mark {{ Money::format($order->total) }} as paid</button>
                </form>
            @endif
        </x-admin.panel>

        @if($order->delivery)
            <x-admin.panel title="Delivery">
                <p class="text-sm text-ink/75">{{ $order->delivery->fullAddress() }}</p>
                <dl class="mt-3 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-ink/60">Fee</dt><dd>{{ Money::format($order->delivery_fee) }}</dd></div>
                    @if($order->out_for_delivery_at)<div class="flex justify-between"><dt class="text-ink/60">Dispatched</dt><dd>{{ $order->out_for_delivery_at->format('d M, H:i') }}</dd></div>@endif
                    @if($order->delivered_at)<div class="flex justify-between"><dt class="text-ink/60">Delivered</dt><dd>{{ $order->delivered_at->format('d M, H:i') }}</dd></div>@endif
                </dl>

                @unless($finished)
                    <form method="POST" action="{{ route('admin.deliveries.update', $order->delivery) }}" class="mt-4 space-y-2 border-t border-munch/10 pt-4">
                        @csrf @method('PATCH')
                        <p class="label-field">Rider</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="driver_name" value="{{ $order->delivery->driver_name }}" maxlength="255" class="input-field" placeholder="Name" aria-label="Rider name">
                            <input name="driver_phone" value="{{ $order->delivery->driver_phone }}" maxlength="30" class="input-field" placeholder="Phone" aria-label="Rider phone">
                        </div>
                        <label class="label-field !mt-1" for="eta">Estimated minutes</label>
                        <input id="eta" type="number" name="estimated_minutes" min="5" max="240" value="{{ $order->delivery->estimated_minutes }}" class="input-field">
                        <textarea name="notes" rows="2" maxlength="500" class="input-field" placeholder="Delivery notes" aria-label="Delivery notes">{{ $order->delivery->notes }}</textarea>
                        <button type="submit" class="btn-secondary w-full">Save delivery details</button>
                    </form>
                @else
                    @if($order->delivery->driver_name)
                        <p class="mt-3 text-sm">Rider: {{ $order->delivery->driver_name }} {{ $order->delivery->driver_phone }}</p>
                    @endif
                @endunless
            </x-admin.panel>
        @endif
    </div>
</div>
@endsection
