<?php

declare(strict_types=1);

use App\Models\Track;
use App\Models\Genre;
use App\Jobs\ProcessTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->genre = Genre::factory()->create(['name' => 'Test Genre']);
    
    $this->track = Track::factory()->create([
        'status' => 'pending',
        'title' => 'Test Track',
        'genre_id' => $this->genre->id,
    ]);
    
    Queue::fake();
    
    // Set up basic auth for API
    $this->withHeaders([
        'Authorization' => 'Basic ' . base64_encode('admin:password'),
        'Accept' => 'application/json',
    ]);
});

describe('API V1 TrackController', function () {
    it('can list tracks', function () {
        Track::factory()->count(5)->create();
        
        $response = $this->get('/api/v1/tracks');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'artist',
                    'status',
                    'genre',
                    'created_at',
                    'updated_at'
                ]
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page'
            ]
        ]);
        
        expect($response->json('data'))->toHaveCount(6); // 5 + 1 from beforeEach
    });

    it('can show a specific track', function () {
        $response = $this->get("/api/v1/tracks/{$this->track->id}");
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'artist',
                'status',
                'genre',
                'mp3_url',
                'image_url',
                'created_at',
                'updated_at'
            ]
        ]);
        
        expect($response->json('data.id'))->toBe($this->track->id);
        expect($response->json('data.title'))->toBe('Test Track');
    });

    it('returns 404 for non-existent track', function () {
        $response = $this->get('/api/v1/tracks/999999');
        
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Track not found'
        ]);
    });

    it('can start track processing', function () {
        $response = $this->post("/api/v1/tracks/{$this->track->id}/start");
        
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Track processing started successfully',
            'status' => 'processing'
        ]);
        
        expect($this->track->fresh()->status)->toBe('processing');
        Queue::assertPushed(ProcessTrack::class);
    });

    it('can start track processing with force redownload', function () {
        $this->track->update(['status' => 'completed']);
        
        $response = $this->post("/api/v1/tracks/{$this->track->id}/start", [
            'force_redownload' => true
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Track processing started successfully',
            'status' => 'processing'
        ]);
        
        expect($this->track->fresh()->status)->toBe('processing');
        Queue::assertPushed(ProcessTrack::class);
    });

    it('cannot start already processing track', function () {
        $this->track->update(['status' => 'processing']);
        
        $response = $this->post("/api/v1/tracks/{$this->track->id}/start");
        
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Track cannot be started in its current state'
        ]);
        
        Queue::assertNotPushed(ProcessTrack::class);
    });

    it('can stop track processing', function () {
        $this->track->update(['status' => 'processing']);
        
        $response = $this->post("/api/v1/tracks/{$this->track->id}/stop");
        
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Track processing stopped successfully',
            'status' => 'stopped'
        ]);
        
        expect($this->track->fresh()->status)->toBe('stopped');
    });

    it('cannot stop non-processing track', function () {
        $this->track->update(['status' => 'completed']);
        
        $response = $this->post("/api/v1/tracks/{$this->track->id}/stop");
        
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Track cannot be stopped in its current state'
        ]);
    });

    it('can retry track processing', function () {
        $this->track->update(['status' => 'failed']);
        
        $response = $this->post("/api/v1/tracks/{$this->track->id}/retry");
        
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Track processing retried successfully',
            'status' => 'processing'
        ]);
        
        expect($this->track->fresh()->status)->toBe('processing');
        Queue::assertPushed(ProcessTrack::class);
    });

    it('cannot retry track that is not failed or stopped', function () {
        $this->track->update(['status' => 'completed']);
        
        $response = $this->post("/api/v1/tracks/{$this->track->id}/retry");
        
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Track cannot be retried in its current state'
        ]);
        
        Queue::assertNotPushed(ProcessTrack::class);
    });

    it('can get track status', function () {
        $response = $this->get("/api/v1/tracks/{$this->track->id}/status");
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'progress',
                'started_at',
                'completed_at',
                'error_message'
            ]
        ]);
        
        expect($response->json('data.status'))->toBe('pending');
    });

    it('can perform bulk actions', function () {
        $tracks = Track::factory()->count(3)->create(['status' => 'pending']);
        $trackIds = $tracks->pluck('id')->toArray();
        
        $response = $this->post('/api/v1/tracks/bulk/action', [
            'action' => 'start',
            'track_ids' => $trackIds
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'results' => [
                'successful',
                'failed'
            ]
        ]);
        
        foreach ($tracks as $track) {
            expect($track->fresh()->status)->toBe('processing');
        }
        
        Queue::assertPushed(ProcessTrack::class, 3);
    });

    it('can get bulk status', function () {
        $tracks = Track::factory()->count(3)->create();
        $trackIds = $tracks->pluck('id')->toArray();
        
        $response = $this->post('/api/v1/tracks/bulk/status', [
            'track_ids' => $trackIds
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'status',
                    'progress'
                ]
            ]
        ]);
        
        expect($response->json('data'))->toHaveCount(3);
    });

    it('validates bulk action parameters', function () {
        $response = $this->post('/api/v1/tracks/bulk/action', [
            'action' => 'invalid_action',
            'track_ids' => []
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['action', 'track_ids']);
    });

    it('handles bulk action with some failures', function () {
        $pendingTrack = Track::factory()->create(['status' => 'pending']);
        $processingTrack = Track::factory()->create(['status' => 'processing']);
        
        $response = $this->post('/api/v1/tracks/bulk/action', [
            'action' => 'start',
            'track_ids' => [$pendingTrack->id, $processingTrack->id]
        ]);
        
        $response->assertStatus(200);
        
        $results = $response->json('results');
        expect($results['successful'])->toHaveCount(1);
        expect($results['failed'])->toHaveCount(1);
    });

    it('filters tracks by status', function () {
        Track::factory()->count(3)->create(['status' => 'completed']);
        Track::factory()->count(2)->create(['status' => 'failed']);
        
        $response = $this->get('/api/v1/tracks?status=completed');
        
        $response->assertStatus(200);
        
        $tracks = $response->json('data');
        foreach ($tracks as $track) {
            expect($track['status'])->toBe('completed');
        }
    });

    it('filters tracks by genre', function () {
        $anotherGenre = Genre::factory()->create();
        Track::factory()->count(2)->create(['genre_id' => $anotherGenre->id]);
        
        $response = $this->get("/api/v1/tracks?genre={$this->genre->id}");
        
        $response->assertStatus(200);
        
        $tracks = $response->json('data');
        foreach ($tracks as $track) {
            expect($track['genre']['id'])->toBe($this->genre->id);
        }
    });

    it('searches tracks by title', function () {
        Track::factory()->create(['title' => 'Unique Search Title']);
        
        $response = $this->get('/api/v1/tracks?search=Unique Search');
        
        $response->assertStatus(200);
        
        $tracks = $response->json('data');
        expect($tracks)->toHaveCount(1);
        expect($tracks[0]['title'])->toBe('Unique Search Title');
    });

    it('paginates tracks correctly', function () {
        Track::factory()->count(25)->create();
        
        $response = $this->get('/api/v1/tracks?per_page=10');
        
        $response->assertStatus(200);
        
        $meta = $response->json('meta');
        expect($meta['per_page'])->toBe(10);
        expect($meta['total'])->toBe(26); // 25 + 1 from beforeEach
        expect($meta['last_page'])->toBe(3);
        
        expect($response->json('data'))->toHaveCount(10);
    });

    it('requires authentication', function () {
        $response = $this->withHeaders([])->get('/api/v1/tracks');
        
        $response->assertStatus(401);
    });

    it('handles invalid track ID gracefully', function () {
        $response = $this->post('/api/v1/tracks/invalid/start');
        
        $response->assertStatus(404);
    });

    it('includes track relationships in response', function () {
        $response = $this->get("/api/v1/tracks/{$this->track->id}");
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        expect($data['genre'])->toHaveKey('id');
        expect($data['genre'])->toHaveKey('name');
        expect($data['genre']['name'])->toBe('Test Genre');
    });

    it('sorts tracks correctly', function () {
        Track::factory()->create(['title' => 'A Track', 'created_at' => now()->subDay()]);
        Track::factory()->create(['title' => 'Z Track', 'created_at' => now()]);
        
        $response = $this->get('/api/v1/tracks?sort=title&direction=asc');
        
        $response->assertStatus(200);
        
        $tracks = $response->json('data');
        expect($tracks[0]['title'])->toBe('A Track');
    });
}); 