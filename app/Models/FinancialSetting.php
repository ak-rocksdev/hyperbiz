<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\BelongsToCompany;

class FinancialSetting extends Model
{
    use BelongsToCompany;
    protected $table = 'fin_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'setting_group',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("fin_setting_{$key}", 3600, function () use ($key) {
            return static::where('setting_key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->setting_value, $setting->setting_type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, ?string $type = null, ?string $group = null): bool
    {
        $setting = static::where('setting_key', $key)->first();

        if ($setting) {
            // Don't update system settings
            if ($setting->is_system) {
                return false;
            }

            $setting->update([
                'setting_value' => static::serializeValue($value, $type ?? $setting->setting_type),
            ]);
        } else {
            static::create([
                'setting_key' => $key,
                'setting_value' => static::serializeValue($value, $type ?? 'string'),
                'setting_type' => $type ?? 'string',
                'setting_group' => $group ?? 'general',
            ]);
        }

        Cache::forget("fin_setting_{$key}");
        return true;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        $settings = static::where('setting_group', $group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->setting_key] = static::castValue($setting->setting_value, $setting->setting_type);
        }

        return $result;
    }

    /**
     * Get all settings as key-value array
     */
    public static function getAllSettings(): array
    {
        $settings = static::all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->setting_key] = static::castValue($setting->setting_value, $setting->setting_type);
        }

        return $result;
    }

    /**
     * Check if a feature is enabled
     */
    public static function isEnabled(string $key): bool
    {
        return (bool) static::get($key, false);
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Serialize value for storage
     */
    protected static function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Clear all cached settings
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("fin_setting_{$setting->setting_key}");
        }
    }
}
