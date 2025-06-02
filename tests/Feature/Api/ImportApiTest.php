<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Track;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ImportApiTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data with unique genre names to avoid conflicts
        Track::factory()->count(5)->create(['status' => 'completed']);
        Track::factory()->count(3)->create(['status' => 'pending']);
        
        // Create genres with unique names to avoid constraint violations
        Genre::factory()->create(['name' => 'Test Genre 1', 'slug' => 'test-genre-1']);
        Genre::factory()->create(['name' => 'Test Genre 2', 'slug' => 'test-genre-2']);
        Genre::factory()->create(['name' => 'Test Genre 3', 'slug' => 'test-genre-3']);
        
        // Clear cache and rate limiters
        Cache::flush();
        RateLimiter::clear('import_json_127.0.0.1');
        RateLimiter::clear('import_discover_127.0.0.1');
        RateLimiter::clear('import_search_127.0.0.1');
        RateLimiter::clear('import_all_127.0.0.1');
        
        Storage::fake('local');
    }

    public function test_import_stats_endpoint_returns_correct_data(): void
    {
        $response = $this->getJson(route('import.stats'));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'stats' => [
                         'total_tracks',
                         'total_genres',
                         'pending_tracks',
                         'processing_tracks',
                         'completed_tracks',
                         'failed_tracks',
                         'pending_jobs',
                         'failed_jobs',
                         'last_updated'
                     ]
                 ]);

        $stats = $response->json('stats');
        $this->assertEquals(8, $stats['total_tracks']);
        $this->assertEquals(3, $stats['total_genres']);
        $this->assertEquals(3, $stats['pending_tracks']);
        $this->assertEquals(5, $stats['completed_tracks']);
    }

    public function test_import_progress_endpoint_with_valid_session(): void
    {
        $sessionId = 'import_test_12345abcdef';
        $progressData = [
            'status' => 'running',
            'progress' => 75,
            'message' => 'Processing tracks...',
            'imported' => 15,
            'failed' => 2,
            'total' => 20
        ];

        Cache::put("import_progress_{$sessionId}", $progressData, 3600);

        $response = $this->getJson(route('import.progress', ['sessionId' => $sessionId]));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'progress' => $progressData
                 ]);
    }

    public function test_import_progress_endpoint_with_invalid_session(): void
    {
        $response = $this->getJson(route('import.progress', ['sessionId' => 'nonexistent_session']));

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Session not found or expired'
                 ]);
    }

    public function test_import_progress_endpoint_validates_session_id_format(): void
    {
        $response = $this->getJson(route('import.progress', ['sessionId' => 'invalid-format']));

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid session ID format'
                 ]);
    }

    public function test_json_import_rate_limiting(): void
    {
        Queue::fake();
        
        // Temporarily set a flag to enable rate limiting in testing
        config(['app.test_rate_limiting' => true]);
        
        $file = UploadedFile::fake()->createWithContent(
            'test.json',
            json_encode([['title' => 'Test Track']])
        );

        // Make 5 requests (the limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('import.json'), [
                'source_type' => 'file',
                'json_file' => $file,
                'format' => 'auto'
            ]);
            
            // Debug if not successful
            if ($response->status() !== 200) {
                dump("Request $i failed with status: " . $response->status());
                dump("Response: " . $response->content());
            }
            
            $response->assertStatus(200);
        }

        // 6th request should be rate limited
        $response = $this->postJson(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file,
            'format' => 'auto'
        ]);

        $response->assertStatus(429)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Too many import attempts. Please try again later.'
                 ]);
                 
        // Reset config
        config(['app.test_rate_limiting' => false]);
    }

    public function test_discover_import_rate_limiting(): void
    {
        Queue::fake();

        // Temporarily enable rate limiting for this test
        app()->detectEnvironment(function () {
            return 'production';
        });

        // Make 3 requests (the limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson(route('import.suno-discover'), [
                'section' => 'trending_songs',
                'page_size' => 10,
                'pages' => 1
            ]);
            $response->assertStatus(200);
        }

        // 4th request should be rate limited
        $response = $this->postJson(route('import.suno-discover'), [
            'section' => 'trending_songs',
            'page_size' => 10,
            'pages' => 1
        ]);

        $response->assertStatus(429)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Too many discover import attempts. Please try again later.'
                 ]);
                 
        // Reset environment
        app()->detectEnvironment(function () {
            return 'testing';
        });
    }

    public function test_search_import_rate_limiting(): void
    {
        Queue::fake();

        // Temporarily enable rate limiting for this test
        app()->detectEnvironment(function () {
            return 'production';
        });

        // Make 3 requests (the limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson(route('import.suno-search'), [
                'size' => 10,
                'pages' => 1,
                'rank_by' => 'upvote_count'
            ]);
            $response->assertStatus(200);
        }

        // 4th request should be rate limited
        $response = $this->postJson(route('import.suno-search'), [
            'size' => 10,
            'pages' => 1,
            'rank_by' => 'upvote_count'
        ]);

        $response->assertStatus(429)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Too many search import attempts. Please try again later.'
                 ]);
                 
        // Reset environment
        app()->detectEnvironment(function () {
            return 'testing';
        });
    }

    public function test_unified_import_rate_limiting(): void
    {
        Queue::fake();

        // Temporarily enable rate limiting for this test
        app()->detectEnvironment(function () {
            return 'production';
        });

        // Make 2 requests (the limit)
        for ($i = 0; $i < 2; $i++) {
            $response = $this->postJson(route('import.suno-all'), [
                'sources' => ['discover'],
                'discover_pages' => 1,
                'discover_size' => 10
            ]);
            $response->assertStatus(200);
        }

        // 3rd request should be rate limited
        $response = $this->postJson(route('import.suno-all'), [
            'sources' => ['discover'],
            'discover_pages' => 1,
            'discover_size' => 10
        ]);

        $response->assertStatus(429)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Too many unified import attempts. Please try again later.'
                 ]);
                 
        // Reset environment
        app()->detectEnvironment(function () {
            return 'testing';
        });
    }

    public function test_json_import_validates_file_size(): void
    {
        // Create a file that's too large (over 10MB)
        $largeFile = UploadedFile::fake()->create('large.json', 11 * 1024); // 11MB

        $response = $this->postJson(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $largeFile,
            'format' => 'auto'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['json_file']);
    }

    public function test_json_import_validates_file_type(): void
    {
        $invalidFile = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->postJson(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $invalidFile,
            'format' => 'auto'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['json_file']);
    }

    public function test_json_import_validates_json_content(): void
    {
        $invalidJsonFile = UploadedFile::fake()->createWithContent(
            'invalid.json',
            '{"invalid": json content'
        );

        $response = $this->postJson(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $invalidJsonFile,
            'format' => 'auto'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid JSON format in uploaded file'
                 ]);
    }

    public function test_json_import_validates_url_format(): void
    {
        $response = $this->postJson(route('import.json'), [
            'source_type' => 'url',
            'json_url' => 'not-a-valid-url',
            'format' => 'auto'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['json_url']);
    }

    public function test_json_import_with_remote_url(): void
    {
        Queue::fake();
        Http::fake([
            'https://example.com/tracks.json' => Http::response([
                ['title' => 'Remote Track', 'audio_url' => 'https://cdn1.suno.ai/test.mp3']
            ], 200)
        ]);

        $response = $this->postJson(route('import.json'), [
            'source_type' => 'url',
            'json_url' => 'https://example.com/tracks.json',
            'format' => 'object',
            'limit' => 100,
            'dry_run' => true
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'session_id',
                     'message'
                 ]);

        $this->assertStringStartsWith('import_json_', $response->json('session_id'));
    }

    public function test_discover_import_validates_section(): void
    {
        $response = $this->postJson(route('import.suno-discover'), [
            'section' => 'invalid_section',
            'page_size' => 20,
            'pages' => 1
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['section']);
    }

    public function test_discover_import_validates_page_size_range(): void
    {
        $response = $this->postJson(route('import.suno-discover'), [
            'section' => 'trending_songs',
            'page_size' => 0,
            'pages' => 1
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['page_size']);

        $response = $this->postJson(route('import.suno-discover'), [
            'section' => 'trending_songs',
            'page_size' => 101,
            'pages' => 1
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['page_size']);
    }

    public function test_search_import_validates_rank_by(): void
    {
        $response = $this->postJson(route('import.suno-search'), [
            'size' => 20,
            'pages' => 1,
            'rank_by' => 'invalid_rank'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['rank_by']);
    }

    public function test_search_import_validates_size_range(): void
    {
        $response = $this->postJson(route('import.suno-search'), [
            'size' => 0,
            'pages' => 1,
            'rank_by' => 'upvote_count'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['size']);

        $response = $this->postJson(route('import.suno-search'), [
            'size' => 101,
            'pages' => 1,
            'rank_by' => 'upvote_count'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['size']);
    }

    public function test_unified_import_validates_sources(): void
    {
        $response = $this->postJson(route('import.suno-all'), [
            'sources' => []
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sources']);

        $response = $this->postJson(route('import.suno-all'), [
            'sources' => ['invalid_source']
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sources.0']);
    }

    public function test_unified_import_requires_json_file_when_json_source_included(): void
    {
        $response = $this->postJson(route('import.suno-all'), [
            'sources' => ['json', 'discover']
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['json_file']);
    }

    public function test_import_endpoints_return_proper_error_responses(): void
    {
        // Test JSON import with missing required fields
        $response = $this->postJson(route('import.json'), []);
        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'errors'
                 ]);

        // Test discover import with missing required fields
        $response = $this->postJson(route('import.suno-discover'), []);
        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'errors'
                 ]);

        // Test search import with missing required fields
        $response = $this->postJson(route('import.suno-search'), []);
        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'errors'
                 ]);

        // Test unified import with missing required fields
        $response = $this->postJson(route('import.suno-all'), []);
        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'errors'
                 ]);
    }

    public function test_import_endpoints_log_activities(): void
    {
        Queue::fake();
        
        $file = UploadedFile::fake()->createWithContent(
            'test.json',
            json_encode([['title' => 'Test Track']])
        );

        $response = $this->postJson(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file,
            'format' => 'object',
            'dry_run' => true
        ]);

        $response->assertStatus(200);

        // Check that the session was created and logged
        $sessionId = $response->json('session_id');
        $this->assertNotNull($sessionId);
        
        // Verify progress was cached
        $progress = Cache::get("import_progress_{$sessionId}");
        $this->assertNotNull($progress);
        $this->assertEquals('running', $progress['status']);
    }

    public function test_import_endpoints_support_all_parameters(): void
    {
        Queue::fake();
        
        $file = UploadedFile::fake()->createWithContent(
            'test.json',
            json_encode([['title' => 'Test Track']])
        );

        // Test JSON import with all parameters
        $response = $this->postJson(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file,
            'format' => 'object',
            'field' => 'data',
            'limit' => 100,
            'skip' => 0,
            'dry_run' => true,
            'process' => false
        ]);

        $response->assertStatus(200);

        // Test discover import with all parameters
        $response = $this->postJson(route('import.suno-discover'), [
            'section' => 'trending_songs',
            'page_size' => 25,
            'pages' => 2,
            'start_index' => 10,
            'dry_run' => true,
            'process' => false
        ]);

        $response->assertStatus(200);

        // Test search import with all parameters
        $response = $this->postJson(route('import.suno-search'), [
            'term' => 'electronic music',
            'size' => 30,
            'pages' => 3,
            'rank_by' => 'play_count',
            'instrumental' => true,
            'dry_run' => true,
            'process' => false
        ]);

        $response->assertStatus(200);
    }
} 