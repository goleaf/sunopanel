<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $description = null): void
    {
        $stringValue = static::valueToString($value, $type);
        
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
            ]
        );
        
        // Clear cache
        Cache::forget("setting_{$key}");
    }

    /**
     * Cast value to proper type
     */
    protected static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Convert value to string for storage
     */
    protected static function valueToString(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function all(): array
    {
        return Cache::remember('all_settings', 3600, function () {
            $settings = static::query()->get();
            $result = [];
            
            foreach ($settings as $setting) {
                $result[$setting->key] = static::castValue($setting->value, $setting->type);
            }
            
            return $result;
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('all_settings');
        
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting_{$key}");
        }
    }
}
