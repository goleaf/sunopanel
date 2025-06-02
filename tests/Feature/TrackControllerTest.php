<?php

declare(strict_types=1);

use App\Models\Track;
use App\Models\Genre;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create some test data
    $this->tracks = Track::factory()->count(5)->create();
    $this->genres = Genre::factory()->count(3)->create();
    
    // Attach genres to tracks
    $this->tracks->each(function ($track) {
        $track->genres()->attach($this->genres->random(2));
    });

    // Create settings
    Setting::firstOrCreate(
        ['key' => 'youtube_visibility_filter'],
        [
            'value' => 'all',
            'type' => 'string',
        ]
    );
    
    Setting::firstOrCreate(
        ['key' => 'show_youtube_column'],
        [
            'value' => 'true',
            'type' => 'boolean',
        ]
    );
});

describe('Track Index', function () {
    it('displays tracks index page', function () {
        $response = $this->get('/tracks');
        
        $response->assertStatus(200)
            ->assertViewIs('tracks.index')
            ->assertViewHas('tracks')
            ->assertViewHas('genres')
            ->assertViewHas('statistics');
    });

    it('can search tracks by title', function () {
        $track = Track::factory()->create(['title' => 'Unique Search Title']);
        
        $response = $this->get('/tracks?search=Unique');
        
        $response->assertStatus(200)
            ->assertSee('Unique Search Title');
    });

    it('can filter tracks by status', function () {
        Track::factory()->create(['status' => 'completed']);
        Track::factory()->create(['status' => 'failed']);
        
        $response = $this->get('/tracks?status=completed');
        
        $response->assertStatus(200);
    });

    it('can filter tracks by genre', function () {
        $genre = Genre::factory()->create(['name' => 'Test Genre']);
        $track = Track::factory()->create();
        $track->genres()->attach($genre);
        
        $response = $this->get("/tracks?genre={$genre->id}");
        
        $response->assertStatus(200);
    });

    it('respects youtube visibility filter setting', function () {
        Setting::set('youtube_visibility_filter', 'uploaded');
        
        Track::factory()->create(['youtube_video_id' => 'abc123']);
        Track::factory()->create(['youtube_video_id' => null]);
        
        $response = $this->get('/tracks');
        
        $response->assertStatus(200);
    });
});

describe('Track Show', function () {
    it('displays individual track page', function () {
        $track = Track::factory()->create();
        
        $response = $this->get("/tracks/{$track->id}");
        
        $response->assertStatus(200)
            ->assertViewIs('tracks.show')
            ->assertViewHas('track')
            ->assertSee($track->title);
    });

    it('returns 404 for non-existent track', function () {
        $response = $this->get('/tracks/999999');
        
        $response->assertStatus(404);
    });
});

describe('Track Status API', function () {
    it('returns track status as json', function () {
        $track = Track::factory()->create([
            'status' => 'processing',
            'progress' => 50,
        ]);
        
        $response = $this->get("/tracks/{$track->id}/status");
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'processing',
                'progress' => 50,
            ]);
    });
});

describe('Track Actions', function () {
    it('can delete a track', function () {
        $track = Track::factory()->create();
        
        $response = $this->delete("/tracks/{$track->id}");
        
        $response->assertRedirect()
            ->assertSessionHas('success');
        
        expect(Track::find($track->id))->toBeNull();
    });

    it('can retry a failed track', function () {
        $track = Track::factory()->create([
            'status' => 'failed',
            'error_message' => 'Some error',
        ]);
        
        $response = $this->post("/tracks/{$track->id}/retry");
        
        $response->assertRedirect()
            ->assertSessionHas('success');
        
        $track->refresh();
        expect($track->status)->toBe('pending')
            ->and($track->error_message)->toBeNull();
    });

    it('can retry all failed tracks', function () {
        Track::factory()->count(3)->create(['status' => 'failed']);
        Track::factory()->create(['status' => 'completed']);
        
        $response = $this->post('/tracks/retry-all');
        
        $response->assertRedirect()
            ->assertSessionHas('success');
        
        $failedTracks = Track::where('status', 'failed')->count();
        expect($failedTracks)->toBe(0);
    });

    it('can toggle youtube status', function () {
        $track = Track::factory()->create(['youtube_enabled' => true]);
        
        $response = $this->post("/tracks/{$track->id}/toggle-youtube-status");
        
        $response->assertStatus(200)
            ->assertJson(['youtube_enabled' => false]);
        
        $track->refresh();
        expect($track->youtube_enabled)->toBeFalse();
    });
});

describe('Track Validation', function () {
    it('validates retry action only works on failed tracks', function () {
        $track = Track::factory()->create(['status' => 'completed']);
        
        $response = $this->post("/tracks/{$track->id}/retry");
        
        $response->assertRedirect()
            ->assertSessionHas('error');
    });

    it('handles non-existent track gracefully', function () {
        $response = $this->delete('/tracks/999999');
        
        $response->assertStatus(404);
    });
}); 