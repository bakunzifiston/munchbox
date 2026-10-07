<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BusinessSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("business_setting.{$key}", 3600, function () use ($key) {
            return static::query()->where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'json' => json_decode($setting->value ?? 'null', true),
            default => $setting->value ?? $default,
        };
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string', ?string $label = null): self
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;

        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'group' => $group,
                'type' => $type,
                'label' => $label,
            ]
        );

        Cache::forget("business_setting.{$key}");

        return $setting;
    }

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => Cache::forget("business_setting.{$setting->key}"));
        static::deleted(fn (self $setting) => Cache::forget("business_setting.{$setting->key}"));
    }
}
