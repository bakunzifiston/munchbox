@extends('layouts.admin')

@section('title', $customer->name)
@section('heading', $customer->name)
@section('subheading', 'Customer profile and order history')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.customers.index') }}" class="text-sm font-bold text-box hover:underline">← All customers</a>
</div>

<x-admin.kpi-grid :items="$kpis" />

<div class="grid gap-6 lg:grid-cols-3">
    <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-extrabold text-munch">Contact</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">Phone</dt>
                <dd class="mt-0.5"><a href="tel:{{ $customer->phone }}" class="font-semibold text-box hover:underline">{{ $customer->phone }}</a></dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-ink/40">Email</dt>
                <dd class="mt-0.5 text-ink/70">{{ $customer->email ?? '—' }}</dd>
            </div>
        </dl>

        <h3 class="mt-6 text-sm font-extrabold text-munch">Addresses</h3>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse($customer->addresses as $address)
                <li class="rounded-2xl bg-[#F7F5F1] px-3 py-2.5 text-ink/70">{{ $address->fullAddress() }}</li>
            @empty
                <li class="text-ink/40">No saved addresses</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm lg:col-span-2">
        <h2 class="text-lg font-extrabold text-munch">Orders</h2>
        <ul class="mt-4 divide-y divide-munch/8">
            @forelse($customer->orders as $order)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                    <div>
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold text-munch hover:text-box">{{ $order->order_number }}</a>
                        <p class="text-xs text-ink/45">{{ $order->created_at->format('d M Y · H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-admin.status-badge :status="$order->status" />
                        <span class="font-bold text-munch">{{ $order->formattedTotal() }}</span>
                    </div>
                </li>
            @empty
                <li class="py-8 text-center text-ink/40">No orders yet.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
