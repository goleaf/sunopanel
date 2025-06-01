<?php

declare(strict_types=1);

use App\Models\YouTubeAccount;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = YouTubeAccount::factory()->create([
        'channel_id' => 'UC123456789',
        'channel_title' => 'Test Channel',
        'email' => 'test@example.com',
        'access_token' => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'token_expires_at' => now()->addHour(),
        'is_active' => true,
    ]);
});

describe('YouTubeAccount Model', function () {
    it('can be created with factory', function () {
        expect($this->account)->toBeInstanceOf(YouTubeAccount::class);
        expect($this->account->channel_id)->toBe('UC123456789');
        expect($this->account->channel_title)->toBe('Test Channel');
        expect($this->account->email)->toBe('test@example.com');
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'channel_id', 'channel_title', 'email', 'access_token', 
            'refresh_token', 'token_expires_at', 'is_active', 'last_used_at'
        ];
        
        expect($this->account->getFillable())->toBe($fillable);
    });

    it('has correct hidden attributes', function () {
        $hidden = ['access_token', 'refresh_token'];
        expect($this->account->getHidden())->toBe($hidden);
    });

    it('casts attributes correctly', function () {
        expect($this->account->getCasts())->toHaveKey('token_expires_at');
        expect($this->account->getCasts())->toHaveKey('last_used_at');
        expect($this->account->getCasts())->toHaveKey('is_active');
    });

    it('has tracks relationship', function () {
        $track = Track::factory()->create(['youtube_account_id' => $this->account->id]);
        
        expect($this->account->tracks)->toHaveCount(1);
        expect($this->account->tracks->first())->toBeInstanceOf(Track::class);
        expect($this->account->tracks->first()->id)->toBe($track->id);
    });

    it('can check if token is expired', function () {
        // Test non-expired token
        $this->account->update(['token_expires_at' => now()->addHour()]);
        expect($this->account->isTokenExpired())->toBeFalse();

        // Test expired token
        $this->account->update(['token_expires_at' => now()->subHour()]);
        expect($this->account->isTokenExpired())->toBeTrue();

        // Test null token
        $this->account->update(['token_expires_at' => null]);
        expect($this->account->isTokenExpired())->toBeTrue();
    });

    it('can get display name', function () {
        expect($this->account->getDisplayName())->toBe('Test Channel (test@example.com)');
        
        // Test with null channel title
        $this->account->update(['channel_title' => null]);
        expect($this->account->getDisplayName())->toBe('test@example.com');
    });

    it('can update last used timestamp', function () {
        $originalTime = $this->account->last_used_at;
        
        $this->account->updateLastUsed();
        
        expect($this->account->fresh()->last_used_at)->not->toBe($originalTime);
        expect($this->account->fresh()->last_used_at)->toBeInstanceOf(Carbon\Carbon::class);
    });

    it('has active scope', function () {
        YouTubeAccount::factory()->create(['is_active' => false]);
        
        $activeAccounts = YouTubeAccount::active()->get();
        
        expect($activeAccounts)->toHaveCount(1);
        expect($activeAccounts->first()->id)->toBe($this->account->id);
    });

    it('has valid token scope', function () {
        // Create account with expired token
        YouTubeAccount::factory()->create([
            'token_expires_at' => now()->subHour(),
            'is_active' => true
        ]);
        
        $validAccounts = YouTubeAccount::withValidToken()->get();
        
        expect($validAccounts)->toHaveCount(1);
        expect($validAccounts->first()->id)->toBe($this->account->id);
    });

    it('can get upload count for date range', function () {
        // Create tracks uploaded today
        Track::factory()->count(3)->create([
            'youtube_account_id' => $this->account->id,
            'youtube_uploaded_at' => now(),
            'status' => 'uploadedToYoutube'
        ]);

        // Create track uploaded yesterday
        Track::factory()->create([
            'youtube_account_id' => $this->account->id,
            'youtube_uploaded_at' => now()->subDay(),
            'status' => 'uploadedToYoutube'
        ]);

        $todayCount = $this->account->getUploadCount(now()->startOfDay(), now()->endOfDay());
        $weekCount = $this->account->getUploadCount(now()->subWeek(), now());

        expect($todayCount)->toBe(3);
        expect($weekCount)->toBe(4);
    });

    it('can get total upload count', function () {
        Track::factory()->count(5)->create([
            'youtube_account_id' => $this->account->id,
            'status' => 'uploadedToYoutube'
        ]);

        Track::factory()->count(2)->create([
            'youtube_account_id' => $this->account->id,
            'status' => 'completed'
        ]);

        expect($this->account->getTotalUploadCount())->toBe(5);
    });

    it('can check daily upload limit', function () {
        // Test under limit
        Track::factory()->count(5)->create([
            'youtube_account_id' => $this->account->id,
            'youtube_uploaded_at' => now(),
            'status' => 'uploadedToYoutube'
        ]);

        expect($this->account->canUploadToday())->toBeTrue();

        // Test at limit (assuming daily limit is 100)
        Track::factory()->count(95)->create([
            'youtube_account_id' => $this->account->id,
            'youtube_uploaded_at' => now(),
            'status' => 'uploadedToYoutube'
        ]);

        expect($this->account->canUploadToday())->toBeFalse();
    });

    it('can refresh access token', function () {
        $originalToken = $this->account->access_token;
        $originalExpiry = $this->account->token_expires_at;

        // Mock the token refresh (in real implementation this would call YouTube API)
        $this->account->update([
            'access_token' => 'new_access_token',
            'token_expires_at' => now()->addHours(2)
        ]);

        expect($this->account->access_token)->not->toBe($originalToken);
        expect($this->account->token_expires_at)->not->toBe($originalExpiry);
    });

    it('can be converted to array', function () {
        $array = $this->account->toArray();

        expect($array)->toHaveKey('id');
        expect($array)->toHaveKey('channel_id');
        expect($array)->toHaveKey('channel_title');
        expect($array)->toHaveKey('email');
        expect($array)->not->toHaveKey('access_token'); // Should be hidden
        expect($array)->not->toHaveKey('refresh_token'); // Should be hidden
    });
}); 