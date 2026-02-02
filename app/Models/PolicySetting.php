<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PolicySetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get a setting value with optional default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            "policy_setting_{$key}",
            now()->addHours(1),
            fn () => static::where('key', $key)->first()
        );

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();
        
        if ($setting) {
            $setting->update(['value' => (string) $value]);
            Cache::forget("policy_setting_{$key}");
        }
    }

    /**
     * Get all settings as an associative array.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('policy_settings_all', now()->addHours(1), function () {
            $settings = [];
            foreach (static::all() as $setting) {
                $settings[$setting->key] = static::getValue($setting->key);
            }
            return $settings;
        });
    }
}
