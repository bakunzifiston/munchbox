@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Settings')
@section('subheading', 'Contact details, fees and operating rules used across the shop')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-4">
    @csrf @method('PATCH')
    @foreach($settings as $group => $items)
        <x-admin.panel :title="ucfirst($group)">
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($items as $setting)
                    @php $value = old('settings.'.$setting->key, $setting->value); @endphp
                    <div>
                        <label for="setting-{{ $setting->key }}" class="label-field">{{ $setting->label ?? str_replace('_', ' ', $setting->key) }}</label>
                        @if($setting->type === 'boolean')
                            <label class="flex min-h-[44px] items-center gap-2 text-sm">
                                <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                <input id="setting-{{ $setting->key }}" type="checkbox" name="settings[{{ $setting->key }}]" value="1" @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN)) class="rounded border-munch/30 text-box focus:ring-box">
                                Enabled
                            </label>
                        @elseif($setting->key === 'inventory_deduct_on_status')
                            <select id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" class="input-field">
                                <option value="confirmed" @selected($value === 'confirmed')>When the order is confirmed</option>
                                <option value="preparing" @selected($value === 'preparing')>When preparation starts</option>
                            </select>
                        @elseif(in_array($setting->type, ['integer', 'decimal'], true))
                            <input id="setting-{{ $setting->key }}" type="number" min="0" step="{{ $setting->type === 'integer' ? 1 : 'any' }}" name="settings[{{ $setting->key }}]" value="{{ $value }}" class="input-field">
                        @else
                            <input id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="{{ $value }}" class="input-field">
                        @endif
                        @error('settings.'.$setting->key)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </x-admin.panel>
    @endforeach

    <x-admin.panel title="System">
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="label-field">Business timezone</dt><dd class="font-semibold text-munch">{{ \App\Support\BusinessTime::timezone() }}</dd></div>
            <div><dt class="label-field">Delivery areas</dt><dd class="text-ink/70">{{ collect(config('menu.delivery_areas'))->pluck('title')->join(', ') }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-ink/45">Timezone is set with BUSINESS_TIMEZONE in the server’s .env file; delivery areas are listed in config/menu.php.</p>
    </x-admin.panel>

    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save settings</button>
    </div>
</form>
@endsection
