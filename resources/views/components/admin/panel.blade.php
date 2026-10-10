@props(['title' => null, 'subtitle' => null])

<section {{ $attributes->class(['rounded-[28px] border border-munch/10 bg-white shadow-sm']) }}>
    @if($title || $subtitle || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-munch/8 px-5 py-4">
            <div>
                @if($title)
                    <h2 class="text-lg font-extrabold text-munch">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 text-sm text-ink/45">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</section>
