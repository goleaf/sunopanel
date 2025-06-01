<?php

declare(strict_types=1);

use App\Models\YouTubeCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->credential = YouTubeCredential::factory()->create([
        'client_id' => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'use_oauth' => true,
    ]);
});

describe('YouTubeCredential Model', function () {
    it('can be created with factory', function () {
        expect($this->credential)->toBeInstanceOf(YouTubeCredential::class);
        expect($this->credential->client_id)->toBe('test_client_id');
        expect($this->credential->client_secret)->toBe('test_client_secret');
        expect($this->credential->use_oauth)->toBeTrue();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'client_id', 'client_secret', 'redirect_uri', 'access_token', 
            'refresh_token', 'token_created_at', 'use_oauth', 'api_key'
        ];
        
        expect($this->credential->getFillable())->toBe($fillable);
    });

    it('has correct hidden attributes', function () {
        $hidden = ['client_secret', 'access_token', 'refresh_token', 'api_key'];
        expect($this->credential->getHidden())->toBe($hidden);
    });

    it('casts attributes correctly', function () {
        expect($this->credential->getCasts())->toHaveKey('token_created_at');
        expect($this->credential->getCasts())->toHaveKey('use_oauth');
        expect($this->credential->getCasts()['use_oauth'])->toBe('boolean');
    });

    it('can check if it has oauth credentials', function () {
        expect($this->credential->hasOAuthCredentials())->toBeTrue();
        
        $credentialWithoutOAuth = YouTubeCredential::factory()->create([
            'client_id' => null,
            'client_secret' => null,
        ]);
        expect($credentialWithoutOAuth->hasOAuthCredentials())->toBeFalse();
    });

    it('can check if it has api key', function () {
        $credentialWithApiKey = YouTubeCredential::factory()->create([
            'api_key' => 'test_api_key',
            'use_oauth' => false,
        ]);
        
        expect($credentialWithApiKey->hasApiKey())->toBeTrue();
        expect($this->credential->hasApiKey())->toBeFalse();
    });

    it('can check if it has valid auth data', function () {
        expect($this->credential->hasValidAuthData())->toBeTrue();
        
        $apiKeyCredential = YouTubeCredential::factory()->create([
            'api_key' => 'test_api_key',
            'use_oauth' => false,
        ]);
        expect($apiKeyCredential->hasValidAuthData())->toBeTrue();
        
        $invalidCredential = YouTubeCredential::factory()->create([
            'client_id' => null,
            'client_secret' => null,
            'api_key' => null,
            'use_oauth' => true,
        ]);
        expect($invalidCredential->hasValidAuthData())->toBeFalse();
    });

    it('can check if it has access token', function () {
        $credentialWithToken = YouTubeCredential::factory()->create([
            'access_token' => 'test_access_token',
        ]);
        
        expect($credentialWithToken->hasAccessToken())->toBeTrue();
        expect($this->credential->hasAccessToken())->toBeFalse();
    });

    it('can check if it has refresh token', function () {
        $credentialWithRefreshToken = YouTubeCredential::factory()->create([
            'refresh_token' => 'test_refresh_token',
        ]);
        
        expect($credentialWithRefreshToken->hasRefreshToken())->toBeTrue();
        expect($this->credential->hasRefreshToken())->toBeFalse();
    });

    it('can get auth method attribute', function () {
        expect($this->credential->auth_method)->toBe('OAuth');
        
        $apiKeyCredential = YouTubeCredential::factory()->create([
            'use_oauth' => false,
        ]);
        expect($apiKeyCredential->auth_method)->toBe('API Key');
    });

    it('has oauth scope', function () {
        YouTubeCredential::factory()->create(['use_oauth' => false]);
        
        $oauthCredentials = YouTubeCredential::oauth()->get();
        
        expect($oauthCredentials)->toHaveCount(1);
        expect($oauthCredentials->first()->id)->toBe($this->credential->id);
    });

    it('has api key scope', function () {
        $apiKeyCredential = YouTubeCredential::factory()->create(['use_oauth' => false]);
        
        $apiKeyCredentials = YouTubeCredential::apiKey()->get();
        
        expect($apiKeyCredentials)->toHaveCount(1);
        expect($apiKeyCredentials->first()->id)->toBe($apiKeyCredential->id);
    });

    it('can get latest credential', function () {
        $newerCredential = YouTubeCredential::factory()->create([
            'created_at' => now()->addMinute(),
        ]);
        
        $latest = YouTubeCredential::getLatest();
        
        expect($latest->id)->toBe($newerCredential->id);
    });

    it('can check if token is expired', function () {
        // Test with no token data
        expect($this->credential->isTokenExpired())->toBeTrue();
        
        // Test with valid token (not expired)
        $validCredential = YouTubeCredential::factory()->create([
            'token_created_at' => time() - 1000,
            'token_expires_in' => 3600, // 1 hour
        ]);
        expect($validCredential->isTokenExpired())->toBeFalse();
        
        // Test with expired token
        $expiredCredential = YouTubeCredential::factory()->create([
            'token_created_at' => time() - 7200, // 2 hours ago
            'token_expires_in' => 3600, // 1 hour expiry
        ]);
        expect($expiredCredential->isTokenExpired())->toBeTrue();
    });

    it('can be converted to array without sensitive data', function () {
        $array = $this->credential->toArray();
        
        expect($array)->toHaveKey('id');
        expect($array)->toHaveKey('client_id');
        expect($array)->toHaveKey('use_oauth');
        expect($array)->not->toHaveKey('client_secret'); // Should be hidden
        expect($array)->not->toHaveKey('access_token'); // Should be hidden
        expect($array)->not->toHaveKey('refresh_token'); // Should be hidden
        expect($array)->not->toHaveKey('api_key'); // Should be hidden
    });
}); 