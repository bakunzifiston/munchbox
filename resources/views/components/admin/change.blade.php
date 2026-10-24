@props(['change', 'money' => false, 'label' => 'vs yesterday'])

{{-- Absolute and percentage change; no percentage when the previous value was zero. --}}
@php
    $diff = (float) $change['diff'];
    $percent = $change['percent'];
    $tone = $diff > 0 ? 'text-emerald-700' : ($diff < 0 ? 'text-rose-700' : 'text-ink/45');
    $amount = $money ? \App\Support\Money::format(abs($diff)) : number_format(abs($diff));
@endphp

<p {{ $attributes->class(['text-xs font-semibold', $tone]) }}>
    @if($diff == 0)
        Same as yesterday
    @else
        {{ $diff > 0 ? '+' : '−' }}{{ $amount }}
        @if($percent !== null)
            ({{ $percent > 0 ? '+' : '' }}{{ $percent }}%)
        @endif
        <span class="font-normal text-ink/45">{{ $label }}</span>
    @endif
</p>
