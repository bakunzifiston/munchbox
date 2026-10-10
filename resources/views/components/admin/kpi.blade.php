@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'default',
])

@php
    $tones = [
        'default' => 'bg-white border-munch/10',
        'warn' => 'bg-amber-50 border-amber-200',
        'danger' => 'bg-rose-50 border-rose-200',
        'success' => 'bg-emerald-50 border-emerald-200',
        'info' => 'bg-sky-50 border-sky-200',
    ];
    $valueTones = [
        'default' => 'text-munch',
        'warn' => 'text-amber-900',
        'danger' => 'text-rose-900',
        'success' => 'text-emerald-900',
        'info' => 'text-sky-900',
    ];
@endphp

<div {{ $attributes->class(['rounded-[24px] border p-4 shadow-sm', $tones[$tone] ?? $tones['default']]) }}>
    <p class="text-[11px] font-bold uppercase tracking-wider text-ink/40">{{ $label }}</p>
    <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $valueTones[$tone] ?? $valueTones['default'] }}">{{ $value }}</p>
    @if($hint)
        <p class="mt-1 text-xs text-ink/45">{{ $hint }}</p>
    @endif
</div>
