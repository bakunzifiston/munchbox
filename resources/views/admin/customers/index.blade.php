@extends('layouts.admin')

@section('title', 'Customers')
@section('heading', 'Customers')
@section('subheading', 'People who order from MunchBox')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<form class="mb-4">
    <input type="search" name="q" value="{{ request('q') }}" class="input-field max-w-sm" placeholder="Search name, phone, email">
</form>

<div class="overflow-hidden rounded-[28px] border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-[#FAF8F4] text-[11px] font-bold uppercase tracking-wider text-ink/40">
            <tr>
                <th class="px-5 py-3">Customer</th>
                <th class="px-5 py-3">Phone</th>
                <th class="px-5 py-3">Orders</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-munch/8">
            @forelse($customers as $customer)
                <tr class="transition hover:bg-[#FAF8F4]/70">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-munch text-sm font-bold text-white">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </span>
                            <div>
                                <p class="font-bold text-munch">{{ $customer->name }}</p>
                                <p class="text-xs text-ink/45">{{ $customer->email ?: 'No email' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <a href="tel:{{ $customer->phone }}" class="font-semibold text-box hover:underline">{{ $customer->phone }}</a>
                    </td>
                    <td class="px-5 py-4 font-semibold text-munch">{{ $customer->orders_count }}</td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold text-box hover:underline">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-14 text-center text-ink/40">No customers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
