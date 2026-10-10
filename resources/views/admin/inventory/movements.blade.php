@extends('layouts.admin')

@section('title', 'Movements')
@section('heading', $item->ingredient->name.' movements')
@section('subheading', 'Stock history for this ingredient')

@section('content')
<a href="{{ route('admin.inventory.index') }}" class="mb-4 inline-flex text-sm font-bold text-box hover:underline">← Inventory</a>

<x-admin.kpi-grid :items="$kpis" />

<div class="overflow-hidden rounded-[28px] border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-[#FAF8F4] text-[11px] font-bold uppercase tracking-wider text-ink/40">
            <tr>
                <th class="px-5 py-3">When</th>
                <th class="px-5 py-3">Type</th>
                <th class="px-5 py-3">Qty</th>
                <th class="px-5 py-3">Balance</th>
                <th class="px-5 py-3">By</th>
                <th class="px-5 py-3">Notes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-munch/8">
            @forelse($movements as $movement)
                <tr class="transition hover:bg-[#FAF8F4]/70">
                    <td class="px-5 py-4">{{ $movement->created_at->format('d M Y · H:i') }}</td>
                    <td class="px-5 py-4">{{ $movement->type->label() }}</td>
                    <td class="px-5 py-4 font-bold {{ $movement->quantity < 0 ? 'text-rose-700' : 'text-munch' }}">{{ $movement->quantity }}</td>
                    <td class="px-5 py-4">{{ $movement->balance_after }}</td>
                    <td class="px-5 py-4">{{ $movement->user?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-ink/60">{{ $movement->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-14 text-center text-ink/40">No movements yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $movements->links() }}</div>
@endsection
