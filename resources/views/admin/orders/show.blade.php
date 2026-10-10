@extends('layouts.admin')

@section('title', $order->order_number)
@section('heading', 'Order '.$order->order_number)
@section('subheading', $order->created_at->format('d M Y · H:i').' · '.$order->fulfillment_type->label())

@section('content')
@php
    $statusColors = [
        'pending' => 'bg-amber-100 text-amber-900',
        'confirmed' => 'bg-sky-100 text-sky-900',
        'preparing' => 'bg-indigo-100 text-indigo-900',
        'ready' => 'bg-teal-100 text-teal-900',
        'out_for_delivery' => 'bg-violet-100 text-violet-900',
        'delivered' => 'bg-emerald-100 text-emerald-900',
        'cancelled' => 'bg-stone-200 text-stone-700',
        'rejected' => 'bg-rose-100 text-rose-900',
    ];
    $statusClass = $statusColors[$order->status->value] ?? 'bg-sage text-munch';
@endphp

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('admin.orders.index') }}" class="text-sm font-bold text-box hover:underline">← All orders</a>
    <span class="inline-flex rounded-full px-3 py-1 text-sm font-bold {{ $statusClass }}">{{ $order->status->label() }}</span>
</div>

<div class="grid gap-6 xl:grid-cols-12">
    {{-- Kitchen ticket / items --}}
    <div class="space-y-5 xl:col-span-8">
        <section class="overflow-hidden rounded-[28px] border border-munch/10 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-munch/10 px-5 py-4">
                <div>
                    <p class="font-mono text-xl font-extrabold text-munch">{{ $order->order_number }}</p>
                    <p class="mt-1 text-sm text-ink/50">{{ $order->items->sum('quantity') }} items · kitchen ticket</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-extrabold text-munch">{{ $order->formattedTotal() }}</p>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink/40">{{ $order->fulfillment_type->label() }}</p>
                </div>
            </div>

            <ul class="divide-y divide-munch/8">
                @foreach($order->items as $item)
                    @php
                        $image = $item->product?->image ?: 'images/sandwiches/classic.jpg';
                        $grouped = $item->options->groupBy('option_group_name');
                    @endphp
                    <li class="p-5">
                        <div class="flex gap-4">
                            <div
                                class="h-24 w-24 shrink-0 rounded-2xl bg-cover bg-center ring-1 ring-munch/10 sm:h-28 sm:w-28"
                                style="background-image: url('{{ asset($image) }}')"
                                role="img"
                                aria-label="{{ $item->product_name }}"
                            ></div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="text-lg font-extrabold text-munch">
                                            <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-munch px-2 text-sm text-white">{{ $item->quantity }}×</span>
                                            {{ $item->product_name }}
                                        </p>
                                        <p class="mt-1 text-sm text-ink/45">
                                            Base {{ number_format($item->unit_price, 0) }} RWF
                                            @if($item->options_total > 0)
                                                · Options +{{ number_format($item->options_total, 0) }} RWF
                                            @endif
                                        </p>
                                    </div>
                                    <p class="text-base font-extrabold text-munch">{{ number_format($item->line_total, 0) }} RWF</p>
                                </div>

                                @if($grouped->isNotEmpty())
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        @foreach($grouped as $group => $options)
                                            <div class="rounded-2xl bg-[#F7F5F1] px-3 py-2.5">
                                                <p class="text-[11px] font-bold uppercase tracking-wide text-ink/40">{{ $group }}</p>
                                                <p class="mt-0.5 text-sm font-semibold text-munch">{{ $options->pluck('option_name')->join(', ') }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if($item->notes)
                                    <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900">
                                        Note: {{ $item->notes }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="space-y-2 border-t border-munch/10 bg-[#FAF8F4] px-5 py-4 text-sm">
                <div class="flex justify-between text-ink/60"><span>Subtotal</span><span>{{ number_format($order->subtotal, 0) }} RWF</span></div>
                @if($order->delivery_fee > 0)
                    <div class="flex justify-between text-ink/60"><span>Delivery</span><span>{{ number_format($order->delivery_fee, 0) }} RWF</span></div>
                @endif
                @if($order->discount > 0)
                    <div class="flex justify-between text-ink/60"><span>Discount</span><span>-{{ number_format($order->discount, 0) }} RWF</span></div>
                @endif
                <div class="flex justify-between text-base font-extrabold text-munch"><span>Total</span><span>{{ $order->formattedTotal() }}</span></div>
            </div>
        </section>

        <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-munch">Status history</h2>
            <ol class="mt-4 space-y-0">
                @forelse($order->statusHistories as $history)
                    <li class="relative flex gap-3 pb-4 last:pb-0">
                        <div class="flex flex-col items-center">
                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-box"></span>
                            @if(! $loop->last)
                                <span class="mt-1 w-px flex-1 bg-munch/10"></span>
                            @endif
                        </div>
                        <div class="min-w-0 pb-1">
                            <p class="font-bold text-munch">{{ $history->to_status->label() }}</p>
                            <p class="text-xs text-ink/45">
                                {{ $history->created_at?->format('d M · H:i') }}
                                @if($history->user) · {{ $history->user->name }}@endif
                            </p>
                            @if($history->notes)
                                <p class="mt-1 text-sm text-ink/60">{{ $history->notes }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-ink/45">No status updates yet.</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- Side panel --}}
    <div class="space-y-5 xl:col-span-4">
        <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-munch">Customer</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">Name</dt>
                    <dd class="mt-0.5 font-semibold text-munch">{{ $order->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">Phone</dt>
                    <dd class="mt-0.5">
                        <a href="tel:{{ $order->customer_phone }}" class="font-semibold text-box hover:underline">{{ $order->customer_phone }}</a>
                    </dd>
                </div>
                @if($order->customer_email)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">Email</dt>
                        <dd class="mt-0.5 break-all text-ink/70">{{ $order->customer_email }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-munch">{{ $order->fulfillment_type->label() }}</h2>
            @if($order->delivery)
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">Address</dt>
                        <dd class="mt-0.5 font-medium text-munch">{{ $order->delivery->fullAddress() }}</dd>
                    </div>
                    @if($order->delivery->estimated_minutes)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">ETA</dt>
                            <dd class="mt-0.5">{{ $order->delivery->estimated_minutes }} minutes</dd>
                        </div>
                    @endif
                    @if($order->delivery->driver_name || $order->delivery->driver_phone)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">Driver</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ $order->delivery->driver_name }}
                                @if($order->delivery->driver_phone)
                                    · <a href="tel:{{ $order->delivery->driver_phone }}" class="text-box hover:underline">{{ $order->delivery->driver_phone }}</a>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            @else
                <p class="mt-3 text-sm text-ink/60">Customer will pick up in-store.</p>
            @endif

            @if($order->notes)
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-950">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-800/70">Customer note</p>
                    <p class="mt-1 font-medium">{{ $order->notes }}</p>
                </div>
            @endif
        </section>

        @if($order->latestPayment || $order->payments->isNotEmpty())
            @php $payment = $order->latestPayment ?: $order->payments->first(); @endphp
            <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm text-sm">
                <h2 class="text-lg font-extrabold text-munch">Payment</h2>
                <p class="mt-3 font-semibold text-munch">{{ $payment->method->label() }}</p>
                <p class="text-ink/55">{{ $payment->status->label() }} · {{ number_format($payment->amount, 0) }} RWF</p>
            </section>
        @endif

        @if(count($transitions))
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="space-y-3 rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')
                <h2 class="text-lg font-extrabold text-munch">Update status</h2>

                <div>
                    <label class="label-field">Next status</label>
                    <select name="status" class="input-field" required>
                        @foreach($transitions as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                @if($order->isDelivery())
                    <div>
                        <label class="label-field">Driver name</label>
                        <input name="driver_name" class="input-field" value="{{ $order->delivery?->driver_name }}">
                    </div>
                    <div>
                        <label class="label-field">Driver phone</label>
                        <input name="driver_phone" class="input-field" value="{{ $order->delivery?->driver_phone }}">
                    </div>
                @endif

                <div>
                    <label class="label-field">Internal note</label>
                    <textarea name="notes" class="input-field" rows="2" placeholder="Optional note for the team"></textarea>
                </div>

                @if(collect($transitions)->contains(fn ($s) => $s->value === 'rejected'))
                    <div>
                        <label class="label-field">Rejection reason</label>
                        <textarea name="rejection_reason" class="input-field" rows="2" placeholder="Only if rejecting"></textarea>
                    </div>
                @endif

                <button class="btn-primary w-full">Update order</button>
            </form>
        @endif
    </div>
</div>
@endsection
