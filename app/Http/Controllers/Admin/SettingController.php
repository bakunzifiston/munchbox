<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = BusinessSetting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group');

        $kpis = [
            ['label' => 'Groups', 'value' => $settings->count(), 'hint' => 'Setting sections'],
            ['label' => 'Settings', 'value' => $settings->flatten()->count(), 'hint' => 'Configurable values'],
            ['label' => 'Delivery fee', 'value' => number_format((float) BusinessSetting::getValue('delivery_fee', 1500), 0).' RWF', 'hint' => 'Current fee'],
            ['label' => 'Min order', 'value' => number_format((float) BusinessSetting::getValue('min_order_amount', 0), 0).' RWF', 'hint' => 'Checkout minimum', 'tone' => 'info'],
        ];

        return view('admin.settings.edit', compact('settings', 'kpis'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            $setting = BusinessSetting::query()->where('key', $key)->first();
            if (! $setting) {
                continue;
            }

            if ($setting->type === 'boolean') {
                $value = $request->boolean("settings.$key") ? '1' : '0';
            }

            $setting->update(['value' => is_array($value) ? json_encode($value) : (string) $value]);
        }

        return back()->with('success', 'Settings saved.');
    }
}
