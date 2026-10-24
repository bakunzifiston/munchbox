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

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = BusinessSetting::query()->get()->keyBy('key');

        $rules = ['settings' => ['required', 'array']];
        foreach ($settings as $key => $setting) {
            $rules["settings.$key"] = match (true) {
                $key === 'email' => ['nullable', 'email', 'max:255'],
                $key === 'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s()-]+$/'],
                $key === 'inventory_deduct_on_status' => ['required', 'in:confirmed,preparing'],
                $setting->type === 'integer' => ['nullable', 'integer', 'min:0'],
                $setting->type === 'decimal' => ['nullable', 'numeric', 'min:0'],
                $setting->type === 'boolean' => ['nullable', 'boolean'],
                $setting->type === 'json' => ['nullable', 'json'],
                default => ['nullable', 'string', 'max:1000'],
            };
        }

        $data = $request->validate($rules);

        foreach ($data['settings'] as $key => $value) {
            $setting = $settings->get($key);
            if (! $setting) {
                continue;
            }

            if ($setting->type === 'boolean') {
                $value = $request->boolean("settings.$key") ? '1' : '0';
            }

            $setting->update(['value' => (string) $value]);
        }

        return back()->with('success', 'Settings saved.');
    }
}
