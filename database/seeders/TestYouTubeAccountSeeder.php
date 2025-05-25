<?php

namespace Database\Seeders;

use App\Models\YouTubeAccount;
use Illuminate\Database\Seeder;

class TestYouTubeAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing accounts
        YouTubeAccount::query()->delete();
        
        // Create test accounts
        $accounts = [
            [
                'name' => 'Music Channel',
                'email' => 'music@example.com',
                'channel_id' => 'UC123456789ABCDEF',
                'channel_name' => 'Awesome Music',
                'access_token' => 'test_access_token_1',
                'refresh_token' => 'test_refresh_token_1',
                'token_expires_at' => now()->addDays(7),
                'account_info' => json_encode([
                    'id' => 'UC123456789ABCDEF',
                    'title' => 'Awesome Music',
                    'description' => 'A channel for awesome music',
                    'subscriberCount' => 1234,
                    'viewCount' => 56789,
                    'videoCount' => 42,
                ]),
                'is_active' => true,
                'last_used_at' => now(),
            ],
            [
                'name' => 'Gaming Channel',
                'email' => 'gaming@example.com',
                'channel_id' => 'UC987654321FEDCBA',
                'channel_name' => 'Epic Gaming',
                'access_token' => 'test_access_token_2',
                'refresh_token' => 'test_refresh_token_2',
                'token_expires_at' => now()->addDays(7),
                'account_info' => json_encode([
                    'id' => 'UC987654321FEDCBA',
                    'title' => 'Epic Gaming',
                    'description' => 'A channel for epic gaming content',
                    'subscriberCount' => 5678,
                    'viewCount' => 98765,
                    'videoCount' => 87,
                ]),
                'is_active' => false,
                'last_used_at' => now()->subDays(2),
            ],
        ];
        
        foreach ($accounts as $accountData) {
            YouTubeAccount::create($accountData);
        }
        
        $this->command->info('Created 2 test YouTube accounts');
    }
} 