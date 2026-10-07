@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-sage text-munch">
        {{ $icon ?? '' }}
    </div>
    <p class="text-base font-extrabold text-munch">{{ $title }}</p>
    @if($description)
        <p class="mt-1 max-w-sm text-sm text-ink/50">{{ $description }}</p>
    @endif
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn-primary mt-5">{{ $actionLabel }}</a>
    @endif
</div>
