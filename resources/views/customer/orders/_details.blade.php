@php
    use App\Enums\OrderStatus;
    use App\Support\Money;

    $fulfillment = $order->fulfillment_type;
    $steps = OrderStatus::customerSteps($fulfillment);
    $stopped = in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true);
    $currentIndex = collect($steps)->search(fn ($step) => $step === $order->status);
    $payment = $order->payments->sortByDesc('id')->first();
@endphp

<div class="space-y-4">
    <section class="card-surface p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-box">Order</p>
                <p class="mt-0.5 font-mono text-lg font-bold text-munch">{{ $order->order_number }}</p>
                <p class="text-sm text-ink/55">{{ $order->created_at->format('d M Y, H:i') }} · {{ $fulfillment->label() }}</p>
            </div>
            <span class="badge {{ $stopped ? 'bg-red-50 text-red-700' : 'bg-sage text-munch' }}">{{ $order->status->customerLabel($fulfillment) }}</span>
        </div>

        @if($stopped)
            <p class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                This order was {{ strtolower($order->status->label()) }}{{ $order->rejection_reason ? ': '.$order->rejection_reason : '.' }}
            </p>
        @else
            <ol class="mt-5 grid gap-3 {{ count($steps) > 5 ? 'sm:grid-cols-6' : 'sm:grid-cols-5' }} sm:gap-2" aria-label="Order progress">
                @foreach($steps as $i => $step)
                    @php
                        $done = $currentIndex !== false && $i <= $currentIndex;
                        $current = $currentIndex === $i;
                        $at = $step->timestampColumn() ? $order->{$step->timestampColumn()} : null;
                    @endphp
                    <li class="flex items-center gap-3 sm:flex-col sm:items-start sm:gap-2" @if($current) aria-current="step" @endif>
                        <span class="flex items-center sm:w-full">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $done ? 'bg-munch text-white' : 'bg-sand text-ink/40' }} {{ $current ? 'ring-4 ring-box/20' : '' }}">
                                @if($done && ! $current)
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" /></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            @unless($loop->last)
                                <span class="ml-2 hidden h-0.5 flex-1 rounded {{ $currentIndex !== false && $i < $currentIndex ? 'bg-munch' : 'bg-sand' }} sm:block"></span>
                            @endunless
                        </span>
                        <span class="text-sm leading-tight {{ $done ? 'font-semibold text-munch' : 'text-ink/45' }}">
                            {{ $step->customerLabel($fulfillment) }}
                            @if($done && $at)
                                <span class="block text-xs font-normal text-ink/45">{{ $at->format('H:i') }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <section class="card-surface p-5">
        <h2 class="font-extrabold text-munch">Items</h2>
        <ul class="mt-3 divide-y divide-munch/5 text-sm">
            @foreach($order->items as $item)
                <li class="py-2.5 first:pt-0">
                    <div class="flex justify-between gap-3 font-semibold text-munch">
                        <span>{{ $item->quantity }}× {{ $item->product_name }}</span>
                        <span class="shrink-0">{{ Money::format($item->line_total) }}</span>
                    </div>
                    @if($item->options->isNotEmpty())
                        <p class="mt-0.5 text-ink/55">
                            {{ $item->options->map(fn ($o) => $o->option_name.((float) $o->price_adjustment > 0 ? ' (+'.Money::format($o->price_adjustment).')' : ''))->join(', ') }}
                        </p>
                    @endif
                    @if($item->notes)
                        <p class="mt-0.5 italic text-ink/50">“{{ $item->notes }}”</p>
                    @endif
                </li>
            @endforeach
        </ul>
        <dl class="mt-3 space-y-1.5 border-t border-munch/10 pt-3 text-sm">
            <div class="flex justify-between"><dt class="text-ink/60">Subtotal</dt><dd>{{ Money::format($order->subtotal) }}</dd></div>
            @if((float) $order->delivery_fee > 0)
                <div class="flex justify-between"><dt class="text-ink/60">Delivery</dt><dd>{{ Money::format($order->delivery_fee) }}</dd></div>
            @endif
            @if((float) $order->discount > 0)
                <div class="flex justify-between"><dt class="text-ink/60">Discount</dt><dd>−{{ Money::format($order->discount) }}</dd></div>
            @endif
            <div class="flex justify-between text-base font-extrabold text-munch"><dt>Total</dt><dd>{{ $order->formattedTotal() }}</dd></div>
        </dl>
    </section>

    <div class="grid gap-4 sm:grid-cols-2">
        <section class="card-surface p-5 text-sm">
            <h2 class="font-extrabold text-munch">Payment</h2>
            @if($payment)
                <p class="mt-2 text-ink/70">{{ $payment->method->label() }}</p>
                <p class="mt-0.5 text-ink/55">Status: {{ $payment->status->label() }}</p>
            @else
                <p class="mt-2 text-ink/55">No payment recorded yet.</p>
            @endif
        </section>

        <section class="card-surface p-5 text-sm">
            <h2 class="font-extrabold text-munch">{{ $order->delivery ? 'Delivery' : 'Pickup' }}</h2>
            @if($order->delivery)
                <p class="mt-2 text-ink/70">{{ $order->delivery->fullAddress() }}</p>
                @if($order->delivery->estimated_minutes && ! $order->status->isTerminal())
                    <p class="mt-0.5 text-ink/55">Usually about {{ $order->delivery->estimated_minutes }} min</p>
                @endif
                @if($order->delivery->driver_name)
                    <p class="mt-2 text-ink/70">Rider: {{ $order->delivery->driver_name }}
                        @if($order->delivery->driver_phone)
                            · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->delivery->driver_phone) }}" class="font-semibold text-box">{{ $order->delivery->driver_phone }}</a>
                        @endif
                    </p>
                @endif
            @else
                <p class="mt-2 text-ink/70">Collect from MunchBox, {{ \App\Models\BusinessSetting::getValue('address', 'Kigali, Rwanda') }}.</p>
            @endif
        </section>
    </div>

    @if($order->statusHistories->isNotEmpty())
        <section class="card-surface p-5 text-sm">
            <h2 class="font-extrabold text-munch">Updates</h2>
            <ul class="mt-3 space-y-2">
                @foreach($order->statusHistories as $history)
                    @php
                        $to = $history->to_status instanceof OrderStatus ? $history->to_status : OrderStatus::tryFrom((string) $history->to_status);
                    @endphp
                    <li class="flex justify-between gap-3">
                        <span class="text-munch">{{ $to?->customerLabel($fulfillment) ?? $history->to_status }}</span>
                        <span class="shrink-0 text-ink/45">{{ $history->created_at?->format('d M, H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
