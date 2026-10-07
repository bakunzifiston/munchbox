@props([
    'size' => 'md', // sm|md|lg
    'showTagline' => false,
    'inverse' => false,
])

@php
    $iconSize = match ($size) {
        'sm' => 'h-8 w-8',
        'lg' => 'h-14 w-14',
        default => 'h-10 w-10',
    };
    $textSize = match ($size) {
        'sm' => 'text-xl',
        'lg' => 'text-4xl',
        default => 'text-2xl',
    };
@endphp

<div {{ $attributes->class(['inline-flex flex-col']) }}>
    <div class="flex items-center gap-2.5">
        <img
            src="{{ asset('images/brand/icon.svg') }}"
            alt="MunchBox"
            class="{{ $iconSize }} object-contain"
        >
        <span class="{{ $textSize }} font-extrabold tracking-tight leading-none">
            <span class="{{ $inverse ? 'text-white' : 'text-munch' }}">Munch</span><span class="text-box">Box</span>
        </span>
    </div>
    @if($showTagline)
        <p class="mt-1.5 pl-[calc(theme(spacing.10)+0.625rem)] text-xs font-medium tracking-wide {{ $inverse ? 'text-white/70' : 'text-munch' }}">
            Unbox the Flavor.
        </p>
    @endif
</div>
