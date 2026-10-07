@extends('layouts.admin')

@section('title', 'Movements')
@section('heading', $item->ingredient->name.' movements')

@section('content')
<a href="{{ route('admin.inventory.index') }}" class="btn-ghost mb-4 inline-flex px-0">← Inventory</a>
<div class="overflow-hidden rounded-3xl border border-munch/10 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-cream/80 text-xs uppercase text-ink/40">
            <tr>
                <th class="px-4 py-3">When</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Qty</th>
                <th class="px-4 py-3">Balance</th>
                <th class="px-4 py-3">By</th>
                <th class="px-4 py-3">Notes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-forest/5">
            @forelse($movements as $movement)
                <tr>
                    <td class="px-4 py-3">{{ $movement->created_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $movement->type->label() }}</td>
                    <td class="px-4 py-3 font-semibold {{ $movement->quantity < 0 ? 'text-red-700' : 'text-munch' }}">{{ $movement->quantity }}</td>
                    <td class="px-4 py-3">{{ $movement->balance_after }}</td>
                    <td class="px-4 py-3">{{ $movement->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $movement->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-ink/40">No movements yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $movements->links() }}</div>
@endsection
