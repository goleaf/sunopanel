<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    /**
     * Essential application settings.
     */
    private array $settings = [
        [
            'key' => 'youtube_visibility_filter',
            'value' => 'all',
            'type' => 'string',
            'description' => 'Global filter for YouTube upload visibility (all, uploaded, not_uploaded)',
        ],
        [
            'key' => 'show_youtube_column',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Show YouTube column in track listings',
        ],
        [
            'key' => 'auto_refresh_interval',
            'value' => '30',
            'type' => 'integer',
            'description' => 'Auto-refresh interval for track status updates (seconds)',
        ],
        [
            'key' => 'max_concurrent_downloads',
            'value' => '3',
            'type' => 'integer',
            'description' => 'Maximum number of concurrent track downloads',
        ],
        [
            'key' => 'enable_notifications',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Enable browser notifications for track status updates',
        ],
        [
            'key' => 'default_track_status',
            'value' => 'pending',
            'type' => 'string',
            'description' => 'Default status for newly created tracks',
        ],
        [
            'key' => 'youtube_upload_enabled',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Enable YouTube upload functionality',
        ],
        [
            'key' => 'track_processing_timeout',
            'value' => '300',
            'type' => 'integer',
            'description' => 'Track processing timeout in seconds',
        ],
        [
            'key' => 'storage_cleanup_enabled',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Enable automatic cleanup of old files',
        ],
        [
            'key' => 'storage_cleanup_days',
            'value' => '30',
            'type' => 'integer',
            'description' => 'Number of days to keep files before cleanup',
        ],
        [
            'key' => 'app_theme',
            'value' => 'light',
            'type' => 'string',
            'description' => 'Application theme (light, dark, auto)',
        ],
        [
            'key' => 'tracks_per_page',
            'value' => '100',
            'type' => 'integer',
            'description' => 'Number of tracks to display per page',
        ],
        [
            'key' => 'enable_debug_mode',
            'value' => '0',
            'type' => 'boolean',
            'description' => 'Enable debug mode for troubleshooting',
        ],
        [
            'key' => 'youtube_stats_update_interval',
            'value' => '3600',
            'type' => 'integer',
            'description' => 'YouTube statistics update interval in seconds',
        ],
        [
            'key' => 'backup_enabled',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Enable automatic database backups',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding application settings...');

        foreach ($this->settings as $settingData) {
            Setting::firstOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => $settingData['value'],
                    'type' => $settingData['type'],
                    'description' => $settingData['description'],
                ]
            );
        }

        $this->command->info('Created ' . count($this->settings) . ' application settings.');
    }
}
