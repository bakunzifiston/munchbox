@extends('layouts.admin')

@section('title', 'Customers')
@section('heading', 'Customers')

@section('content')
<form class="mb-4"><input type="search" name="q" value="{{ request('q') }}" class="input-field max-w-sm" placeholder="Search customers"></form>
<div class="overflow-hidden rounded-3xl border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-cream/80 text-xs uppercase text-ink/40">
            <tr>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Phone</th>
                <th class="px-4 py-3">Orders</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-forest/5">
            @forelse($customers as $customer)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-semibold">{{ $customer->name }}</p>
                        <p class="text-xs text-ink/45">{{ $customer->email }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $customer->phone }}</td>
                    <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-box">View</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-10 text-center text-ink/40">No customers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
