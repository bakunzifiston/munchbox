@props([
    'image' => null,
    'name' => '',
    'lazy' => true,
])

{{-- Product photo, or a branded placeholder when no image has been uploaded yet. --}}
<div {{ $attributes->class(['relative overflow-hidden bg-sand']) }}>
    @if($image)
        <img
            src="{{ asset($image) }}"
            alt="{{ $name }}"
            class="absolute inset-0 h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.04]"
            @if($lazy) loading="lazy" @endif
        >
    @else
        <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-gradient-to-br from-sand to-sage text-munch/35" role="img" aria-label="{{ $name }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-1/4 max-h-12 w-auto">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5c0-3.5 4-6 9-6s9 2.5 9 6H3Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5h18M4.5 16.5h15a1.5 1.5 0 0 1 0 3h-15a1.5 1.5 0 0 1 0-3Z" />
            </svg>
        </div>
    @endif
    {{ $slot }}
</div>
