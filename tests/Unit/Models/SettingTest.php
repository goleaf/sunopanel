<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('Setting Model', function () {
    it('can create a setting', function () {
        $setting = Setting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
            'description' => 'Test setting description',
        ]);

        expect($setting)->toBeInstanceOf(Setting::class)
            ->and($setting->key)->toBe('test_setting')
            ->and($setting->value)->toBe('test_value')
            ->and($setting->type)->toBe('string')
            ->and($setting->description)->toBe('Test setting description');
    });

    it('has correct fillable attributes', function () {
        $fillable = ['key', 'value', 'type', 'description'];
        $setting = new Setting();
        expect($setting->getFillable())->toEqual($fillable);
    });

    it('casts attributes correctly', function () {
        $setting = Setting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
        ]);

        expect($setting->key)->toBeString()
            ->and($setting->value)->toBeString()
            ->and($setting->type)->toBeString();
    });
});

describe('Setting Static Methods', function () {
    it('can get a setting value', function () {
        Setting::create([
            'key' => 'test_key',
            'value' => 'test_value',
            'type' => 'string',
        ]);

        $value = Setting::get('test_key');
        expect($value)->toBe('test_value');
    });

    it('returns default value when setting does not exist', function () {
        $value = Setting::get('non_existent_key', 'default_value');
        expect($value)->toBe('default_value');
    });

    it('can set a setting value', function () {
        Setting::set('new_key', 'new_value', 'string', 'New setting');

        $setting = Setting::where('key', 'new_key')->first();
        expect($setting)->not->toBeNull()
            ->and($setting->value)->toBe('new_value')
            ->and($setting->type)->toBe('string')
            ->and($setting->description)->toBe('New setting');
    });

    it('updates existing setting when setting value', function () {
        Setting::create([
            'key' => 'existing_key',
            'value' => 'old_value',
            'type' => 'string',
        ]);

        Setting::set('existing_key', 'new_value', 'string', 'Updated setting');

        $setting = Setting::where('key', 'existing_key')->first();
        expect($setting->value)->toBe('new_value')
            ->and($setting->description)->toBe('Updated setting');
    });

    it('handles boolean values correctly', function () {
        Setting::set('bool_setting', true, 'boolean');
        
        $value = Setting::get('bool_setting');
        expect($value)->toBeTrue();

        Setting::set('bool_setting', false, 'boolean');
        
        $value = Setting::get('bool_setting');
        expect($value)->toBeFalse();
    });

    it('handles integer values correctly', function () {
        Setting::set('int_setting', 42, 'integer');
        
        $value = Setting::get('int_setting');
        expect($value)->toBe(42);
    });

    it('handles json values correctly', function () {
        $jsonData = ['key1' => 'value1', 'key2' => 'value2'];
        Setting::set('json_setting', $jsonData, 'json');
        
        $value = Setting::get('json_setting');
        expect($value)->toEqual($jsonData);
    });

    it('caches setting values', function () {
        Setting::create([
            'key' => 'cached_key',
            'value' => 'cached_value',
            'type' => 'string',
        ]);

        // First call should hit the database
        $value1 = Setting::get('cached_key');
        expect($value1)->toBe('cached_value');

        // Second call should hit the cache
        $value2 = Setting::get('cached_key');
        expect($value2)->toBe('cached_value');

        // Verify cache key exists
        expect(Cache::has('setting:cached_key'))->toBeTrue();
    });

    it('can clear cache', function () {
        Setting::create([
            'key' => 'cache_test',
            'value' => 'test_value',
            'type' => 'string',
        ]);

        // Get value to cache it
        Setting::get('cache_test');
        expect(Cache::has('setting:cache_test'))->toBeTrue();

        // Clear cache
        Setting::clearCache();
        expect(Cache::has('setting:cache_test'))->toBeFalse();
    });

    it('can clear specific setting cache', function () {
        Setting::create([
            'key' => 'specific_test',
            'value' => 'test_value',
            'type' => 'string',
        ]);

        // Get value to cache it
        Setting::get('specific_test');
        expect(Cache::has('setting:specific_test'))->toBeTrue();

        // Clear specific cache
        Setting::clearCache('specific_test');
        expect(Cache::has('setting:specific_test'))->toBeFalse();
    });
}); 