@props(['items' => []])

@if(count($items))
    <div {{ $attributes->class(['mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4']) }}>
        @foreach($items as $item)
            <x-admin.kpi
                :label="$item['label']"
                :value="$item['value']"
                :hint="$item['hint'] ?? null"
                :tone="$item['tone'] ?? 'default'"
            />
        @endforeach
    </div>
@endif
