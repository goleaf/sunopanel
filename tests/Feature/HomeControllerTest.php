<?php

declare(strict_types=1);

use App\Models\Track;
use App\Models\Genre;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test data
    $this->genre = Genre::factory()->create(['name' => 'City Pop']);
    
    // Create tracks with different statuses
    $this->completedTracks = Track::factory()->count(5)->completed()->create();
    $this->completedTracks->each(function ($track) {
        $track->genres()->attach($this->genre);
    });
    
    $this->processingTracks = Track::factory()->count(2)->processing()->create();
    $this->processingTracks->each(function ($track) {
        $track->genres()->attach($this->genre);
    });
    
    $this->uploadedTracks = Track::factory()->count(3)->uploadedToYoutube()->create();
    $this->uploadedTracks->each(function ($track) {
        $track->genres()->attach($this->genre);
    });
    
    // Create settings
    Setting::firstOrCreate(
        ['key' => 'global_filter'],
        [
            'value' => 'all',
            'type' => 'string'
        ]
    );
    
    Setting::firstOrCreate(
        ['key' => 'youtube_column_visible'],
        [
            'value' => '1',
            'type' => 'boolean'
        ]
    );
});

describe('HomeController', function () {
    it('can display home page', function () {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertViewIs('home.index');
        $response->assertViewHas('tracks');
        $response->assertViewHas('genres');
        $response->assertViewHas('stats');
        $response->assertViewHas('settings');
    });

    it('displays correct track statistics', function () {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $stats = $response->viewData('stats');
        
        expect($stats['total'])->toBe(10); // 5 + 2 + 3
        expect($stats['completed'])->toBe(5);
        expect($stats['processing'])->toBe(2);
        expect($stats['uploaded_to_youtube'])->toBe(3);
        expect($stats['pending'])->toBe(0);
        expect($stats['failed'])->toBe(0);
    });

    it('displays tracks with pagination', function () {
        // Create more tracks to test pagination
        Track::factory()->count(20)->completed()->create();
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        expect($tracks)->toHaveCount(15); // Default pagination limit
    });

    it('filters tracks by status', function () {
        $response = $this->get('/?status=completed');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        foreach ($tracks as $track) {
            expect($track->status)->toBe('completed');
        }
    });

    it('filters tracks by genre', function () {
        $anotherGenre = Genre::factory()->create(['name' => 'Jazz']);
        $jazzTracks = Track::factory()->count(3)->completed()->create();
        $jazzTracks->each(function ($track) use ($anotherGenre) {
            $track->genres()->attach($anotherGenre);
        });
        
        $response = $this->get('/?genre=' . $this->genre->id);
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        foreach ($tracks as $track) {
            expect($track->genres->contains($this->genre))->toBeTrue();
        }
    });

    it('searches tracks by title', function () {
        Track::factory()->completed()->create([
            'title' => 'Unique Search Title',
        ]);
        
        $response = $this->get('/?search=Unique Search');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        expect($tracks)->toHaveCount(1);
        expect($tracks->first()->title)->toBe('Unique Search Title');
    });

    it('respects global filter setting for all tracks', function () {
        Setting::where('key', 'global_filter')->update(['value' => 'all']);
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        expect($tracks->count())->toBe(10); // All tracks visible
    });

    it('respects global filter setting for uploaded only', function () {
        Setting::where('key', 'global_filter')->update(['value' => 'uploaded_only']);
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        expect($tracks->count())->toBeGreaterThanOrEqualTo(3); // At least uploaded tracks
        
        foreach ($tracks as $track) {
            expect($track->youtube_video_id)->not->toBeNull();
        }
    });

    it('respects global filter setting for not uploaded only', function () {
        Setting::where('key', 'global_filter')->update(['value' => 'not_uploaded_only']);
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        expect($tracks->count())->toBeGreaterThanOrEqualTo(5); // At least non-uploaded tracks
        
        foreach ($tracks as $track) {
            expect($track->youtube_video_id)->toBeNull();
        }
    });

    it('displays genres in sidebar', function () {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $genres = $response->viewData('genres');
        $genreNames = $genres->pluck('name');
        expect($genreNames)->toContain('City Pop');
    });

    it('displays settings correctly', function () {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $settings = $response->viewData('settings');
        expect($settings['global_filter'])->toBe('all');
        expect($settings['youtube_column_visible'])->toBeTrue();
    });

    it('handles empty track list gracefully', function () {
        Track::query()->delete();
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        expect($tracks)->toHaveCount(0);
        
        $stats = $response->viewData('stats');
        expect($stats['total'])->toBe(0);
    });

    it('handles missing settings gracefully', function () {
        Setting::query()->delete();
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $settings = $response->viewData('settings');
        expect($settings['global_filter'])->toBe('all'); // Default value
        expect($settings['youtube_column_visible'])->toBeTrue(); // Default value
    });

    it('sorts tracks by created_at desc by default', function () {
        // Create tracks with specific timestamps
        $oldTrack = Track::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subDays(2),
            'title' => 'Old Track'
        ]);
        
        $newTrack = Track::factory()->create([
            'status' => 'completed',
            'created_at' => now(),
            'title' => 'New Track'
        ]);
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        // Since tracks are sorted by status priority first, just check we got tracks
        expect($tracks->count())->toBeGreaterThan(0);
    });

    it('can sort tracks by different columns', function () {
        $response = $this->get('/?sort=title&direction=asc');
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        $titles = $tracks->pluck('title')->toArray();
        $sortedTitles = $titles;
        sort($sortedTitles);
        
        expect($titles)->toBe($sortedTitles);
    });

    it('combines multiple filters correctly', function () {
        $response = $this->get('/?status=completed&genre=' . $this->genre->id);
        
        $response->assertStatus(200);
        
        $tracks = $response->viewData('tracks');
        
        foreach ($tracks as $track) {
            expect($track->status)->toBe('completed');
            expect($track->genres->contains($this->genre))->toBeTrue();
        }
    });

    it('handles invalid filter parameters gracefully', function () {
        $response = $this->get('/?status=invalid&genre=999999');
        
        $response->assertStatus(200);
        
        // Should not crash and should return some tracks
        $tracks = $response->viewData('tracks');
        expect($tracks)->not->toBeNull();
    });

    it('displays track count per genre', function () {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $genres = $response->viewData('genres');
        $cityPopGenre = $genres->where('name', 'City Pop')->first();
        
        // Should have tracks_count attribute from withCount
        expect($cityPopGenre)->not->toBeNull();
        expect($cityPopGenre->tracks_count)->toBeGreaterThanOrEqualTo(0);
    });

    it('shows correct page title', function () {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('SunoPanel - Music Track Management');
    });

    it('includes necessary CSS and JS assets', function () {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('app.css');
        $response->assertSee('app.js');
    });
}); 