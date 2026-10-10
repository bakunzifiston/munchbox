@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Business settings')
@section('subheading', 'Fees, contact info, and operational defaults')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<form method="POST" action="{{ route('admin.settings.update') }}" class="mx-auto max-w-3xl space-y-6">
    @csrf @method('PATCH')
    @foreach($settings as $group => $items)
        <section class="space-y-4 rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold capitalize text-munch">{{ $group }}</h2>
            @foreach($items as $setting)
                <div>
                    <label class="label-field">{{ $setting->label ?? $setting->key }}</label>
                    @if($setting->type === 'boolean')
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" @checked(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>
                            Enabled
                        </label>
                    @else
                        <input name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="input-field">
                    @endif
                </div>
            @endforeach
        </section>
    @endforeach
    <button class="btn-primary">Save settings</button>
</form>
@endsection
