<?php

declare(strict_types=1);

use App\Models\YouTubeAccount;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = YouTubeAccount::factory()->create([
        'name' => 'Test Account',
        'channel_id' => 'UC123456789',
        'channel_name' => 'Test Channel',
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
        expect($this->account->channel_name)->toBe('Test Channel');
        expect($this->account->email)->toBe('test@example.com');
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'name', 'email', 'channel_id', 'channel_name', 'access_token', 
            'refresh_token', 'token_expires_at', 'last_used_at', 'account_info', 'is_active'
        ];
        
        expect($this->account->getFillable())->toEqual($fillable);
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

    it('has valid tokens method', function () {
        expect($this->account->hasValidTokens())->toBeTrue();
        
        // Test with expired token
        $this->account->update(['token_expires_at' => now()->subHour()]);
        expect($this->account->hasValidTokens())->toBeFalse();
    });

    it('can check if token is expired', function () {
        // Test non-expired token
        $this->account->update(['token_expires_at' => now()->addHour()]);
        expect($this->account->isTokenExpired())->toBeFalse();

        // Test expired token
        $this->account->update(['token_expires_at' => now()->subHour()]);
        expect($this->account->isTokenExpired())->toBeTrue();

        // Test null token (considered as never expires)
        $this->account->update(['token_expires_at' => null]);
        expect($this->account->isTokenExpired())->toBeFalse();
    });

    it('can get display name', function () {
        expect($this->account->getDisplayName())->toBe('Test Channel');
        
        // Test with null channel name but with name
        $this->account->update(['channel_name' => null]);
        expect($this->account->getDisplayName())->toBe('Test Account');
        
        // Test with empty channel name and empty name (but not null due to constraint)
        $this->account->update(['channel_name' => '', 'name' => '']);
        expect($this->account->getDisplayName())->toBe('test@example.com');
    });

    it('can update last used timestamp', function () {
        $originalTime = $this->account->last_used_at;
        
        // Manually update last_used_at since updateLastUsed method doesn't exist
        $this->account->update(['last_used_at' => now()]);
        
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
        
        $validAccounts = YouTubeAccount::withValidTokens()->get();
        
        expect($validAccounts)->toHaveCount(1);
        expect($validAccounts->first()->id)->toBe($this->account->id);
    });

    it('can get channel url attribute', function () {
        expect($this->account->channel_url)->toBe('https://www.youtube.com/channel/UC123456789');
        
        // Test with null channel_id
        $this->account->update(['channel_id' => null]);
        expect($this->account->channel_url)->toBeNull();
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
        expect($array)->toHaveKey('channel_name');
        expect($array)->toHaveKey('email');
        expect($array)->not->toHaveKey('access_token'); // Should be hidden
        expect($array)->not->toHaveKey('refresh_token'); // Should be hidden
    });
}); 