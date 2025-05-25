<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'value' => 'string',
    ];

    /**
     * Cache key prefix for settings.
     */
    private const CACHE_PREFIX = 'setting:';

    /**
     * Cache TTL for settings (24 hours).
     */
    private const CACHE_TTL = 86400;

    /**
     * Get a setting value by key with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'float' => (float) $setting->value,
                'array', 'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    /**
     * Set a setting value by key and clear cache.
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $description = null): bool
    {
        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'array', 'json' => json_encode($value),
            default => (string) $value,
        };

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $processedValue,
                'type' => $type,
                'description' => $description,
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting->wasRecentlyCreated || $setting->wasChanged();
    }

    /**
     * Remove a setting and clear cache.
     */
    public static function remove(string $key): bool
    {
        $deleted = self::where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX . $key);
        
        return $deleted > 0;
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $settings = self::query()->get();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when settings are updated or deleted
        static::saved(function (Setting $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        });

        static::deleted(function (Setting $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        });
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings(): array
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
}
