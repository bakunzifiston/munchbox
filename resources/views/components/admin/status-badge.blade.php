@props(['status'])

@php
    $statusEnum = $status instanceof \App\Enums\OrderStatus
        ? $status
        : \App\Enums\OrderStatus::tryFrom((string) $status);

    $styles = match ($statusEnum) {
        \App\Enums\OrderStatus::Pending => 'bg-amber-50 text-amber-800 ring-amber-200',
        \App\Enums\OrderStatus::Confirmed => 'bg-sky-50 text-sky-800 ring-sky-200',
        \App\Enums\OrderStatus::Preparing => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
        \App\Enums\OrderStatus::Ready => 'bg-teal-50 text-teal-800 ring-teal-200',
        \App\Enums\OrderStatus::OutForDelivery => 'bg-violet-50 text-violet-800 ring-violet-200',
        \App\Enums\OrderStatus::Delivered => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        \App\Enums\OrderStatus::Cancelled => 'bg-slate-100 text-slate-600 ring-slate-200',
        \App\Enums\OrderStatus::Rejected => 'bg-rose-50 text-rose-800 ring-rose-200',
        default => 'bg-sage text-munch ring-munch/10',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset', $styles]) }}>
    {{ $statusEnum?->label() ?? (string) $status }}
</span>
