<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Track;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create some test data
        Track::factory()->count(10)->create(['status' => 'completed']);
        Track::factory()->count(5)->create(['status' => 'pending']);
        Track::factory()->count(3)->create(['status' => 'processing']);
        Track::factory()->count(2)->create(['status' => 'failed']);
        Genre::factory()->count(5)->create();
        
        // Clear any existing cache
        Cache::flush();
        
        // Fake storage for file uploads
        Storage::fake('local');
    }

    public function test_import_dashboard_displays_correctly(): void
    {
        $response = $this->get(route('import.index'));

        $response->assertStatus(200);
        $response->assertViewIs('import.index');
        $response->assertViewHas('stats');
        
        // Check that stats are passed correctly
        $stats = $response->viewData('stats');
        $this->assertEquals(20, $stats['total_tracks']);
        $this->assertEquals(5, $stats['total_genres']);
        $this->assertEquals(5, $stats['pending_tracks']);
        $this->assertEquals(3, $stats['processing_tracks']);
        $this->assertEquals(10, $stats['completed_tracks']);
        $this->assertEquals(2, $stats['failed_tracks']);
    }

    public function test_import_dashboard_contains_required_elements(): void
    {
        $response = $this->get(route('import.index'));

        $response->assertSee('Import Dashboard');
        $response->assertSee('JSON Import');
        $response->assertSee('Suno Discover');
        $response->assertSee('Suno Search');
        $response->assertSee('Unified Import');
        $response->assertSee('Total Tracks');
        $response->assertSee('Completed');
        $response->assertSee('Processing');
        $response->assertSee('Queue Jobs');
    }

    public function test_json_import_with_valid_file(): void
    {
        Queue::fake();
        
        $jsonData = [
            ['title' => 'Test Track 1', 'audio_url' => 'https://cdn1.suno.ai/test1.mp3', 'image_url' => 'https://cdn2.suno.ai/test1.jpg', 'tags' => 'electronic'],
            ['title' => 'Test Track 2', 'audio_url' => 'https://cdn1.suno.ai/test2.mp3', 'image_url' => 'https://cdn2.suno.ai/test2.jpg', 'tags' => 'jazz']
        ];
        
        $file = UploadedFile::fake()->createWithContent(
            'tracks.json',
            json_encode($jsonData)
        );

        $response = $this->post(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file,
            'format' => 'object',
            'limit' => 100,
            'skip' => 0,
            'dry_run' => false,
            'process' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('session_id', $responseData);
        $this->assertStringStartsWith('import_json_', $responseData['session_id']);
    }

    public function test_json_import_with_url(): void
    {
        Queue::fake();
        Http::fake([
            'https://example.com/tracks.json' => Http::response([
                ['title' => 'Remote Track', 'audio_url' => 'https://cdn1.suno.ai/remote.mp3', 'image_url' => 'https://cdn2.suno.ai/remote.jpg', 'tags' => 'ambient']
            ], 200)
        ]);

        $response = $this->post(route('import.json'), [
            'source_type' => 'url',
            'json_url' => 'https://example.com/tracks.json',
            'format' => 'auto',
            'limit' => 50,
            'dry_run' => true,
            'process' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_json_import_validation_errors(): void
    {
        // Test missing source type
        $response = $this->post(route('import.json'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source_type']);

        // Test invalid format
        $response = $this->post(route('import.json'), [
            'source_type' => 'file',
            'format' => 'invalid_format',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);

        // Test missing file when source_type is file
        $response = $this->post(route('import.json'), [
            'source_type' => 'file',
            'format' => 'auto',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['json_file']);

        // Test missing URL when source_type is url
        $response = $this->post(route('import.json'), [
            'source_type' => 'url',
            'format' => 'auto',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['json_url']);
    }

    public function test_suno_discover_import(): void
    {
        Queue::fake();

        $response = $this->post(route('import.suno-discover'), [
            'section' => 'trending_songs',
            'page_size' => 20,
            'pages' => 2,
            'start_index' => 0,
            'dry_run' => false,
            'process' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('session_id', $responseData);
        $this->assertStringStartsWith('import_discover_', $responseData['session_id']);
    }

    public function test_suno_discover_import_validation(): void
    {
        // Test missing required fields
        $response = $this->post(route('import.suno-discover'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['section', 'page_size', 'pages']);

        // Test invalid section
        $response = $this->post(route('import.suno-discover'), [
            'section' => 'invalid_section',
            'page_size' => 20,
            'pages' => 1,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['section']);

        // Test invalid page_size range
        $response = $this->post(route('import.suno-discover'), [
            'section' => 'trending_songs',
            'page_size' => 0,
            'pages' => 1,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['page_size']);

        $response = $this->post(route('import.suno-discover'), [
            'section' => 'trending_songs',
            'page_size' => 101,
            'pages' => 1,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['page_size']);
    }

    public function test_suno_search_import(): void
    {
        Queue::fake();

        $response = $this->post(route('import.suno-search'), [
            'term' => 'electronic music',
            'size' => 25,
            'pages' => 3,
            'rank_by' => 'upvote_count',
            'instrumental' => false,
            'dry_run' => false,
            'process' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('session_id', $responseData);
        $this->assertStringStartsWith('import_search_', $responseData['session_id']);
    }

    public function test_suno_search_import_validation(): void
    {
        // Test missing required fields
        $response = $this->post(route('import.suno-search'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['size', 'pages', 'rank_by']);

        // Test invalid rank_by
        $response = $this->post(route('import.suno-search'), [
            'size' => 20,
            'pages' => 1,
            'rank_by' => 'invalid_rank',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rank_by']);

        // Test size range validation
        $response = $this->post(route('import.suno-search'), [
            'size' => 0,
            'pages' => 1,
            'rank_by' => 'upvote_count',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['size']);
    }

    public function test_unified_import(): void
    {
        Queue::fake();
        
        $jsonData = [
            ['title' => 'Unified Track', 'audio_url' => 'https://cdn1.suno.ai/unified.mp3', 'image_url' => 'https://cdn2.suno.ai/unified.jpg', 'tags' => 'experimental']
        ];
        
        $file = UploadedFile::fake()->createWithContent(
            'unified.json',
            json_encode($jsonData)
        );

        $response = $this->post(route('import.suno-all'), [
            'sources' => ['json', 'discover'],
            'json_file' => $file,
            'discover_pages' => 1,
            'discover_size' => 10,
            'dry_run' => false,
            'process' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('session_id', $responseData);
        $this->assertStringStartsWith('import_all_', $responseData['session_id']);
    }

    public function test_unified_import_validation(): void
    {
        // Test missing sources
        $response = $this->post(route('import.suno-all'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sources']);

        // Test empty sources array
        $response = $this->post(route('import.suno-all'), [
            'sources' => [],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sources']);

        // Test invalid source
        $response = $this->post(route('import.suno-all'), [
            'sources' => ['invalid_source'],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sources.0']);
    }

    public function test_get_progress_with_valid_session(): void
    {
        $sessionId = 'test_session_123';
        $progressData = [
            'status' => 'running',
            'progress' => 50,
            'message' => 'Processing tracks...',
            'imported' => 25,
            'failed' => 2,
            'total' => 50,
        ];
        
        Cache::put("import_progress_{$sessionId}", $progressData, 3600);

        $response = $this->get(route('import.progress', ['sessionId' => $sessionId]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'progress' => $progressData,
        ]);
    }

    public function test_get_progress_with_invalid_session(): void
    {
        $response = $this->get(route('import.progress', ['sessionId' => 'nonexistent_session']));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Session not found or expired',
        ]);
    }

    public function test_get_stats_endpoint(): void
    {
        $response = $this->get(route('import.stats'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('stats', $responseData);
        
        $stats = $responseData['stats'];
        $this->assertEquals(20, $stats['total_tracks']);
        $this->assertEquals(5, $stats['total_genres']);
        $this->assertEquals(5, $stats['pending_tracks']);
        $this->assertEquals(3, $stats['processing_tracks']);
        $this->assertEquals(10, $stats['completed_tracks']);
        $this->assertEquals(2, $stats['failed_tracks']);
    }

    public function test_import_with_large_file_limit(): void
    {
        $response = $this->post(route('import.json'), [
            'source_type' => 'url',
            'json_url' => 'https://example.com/large.json',
            'format' => 'auto',
            'limit' => 10001, // Over the max limit
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }

    public function test_import_with_negative_skip_value(): void
    {
        $response = $this->post(route('import.json'), [
            'source_type' => 'url',
            'json_url' => 'https://example.com/tracks.json',
            'format' => 'auto',
            'skip' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['skip']);
    }

    public function test_import_caches_progress_correctly(): void
    {
        Queue::fake();
        
        $file = UploadedFile::fake()->createWithContent(
            'test.json',
            json_encode([['title' => 'Test']])
        );

        $response = $this->post(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file,
            'format' => 'auto',
        ]);

        $sessionId = $response->json('session_id');
        
        // Check that progress is cached
        $progress = Cache::get("import_progress_{$sessionId}");
        $this->assertNotNull($progress);
        $this->assertEquals('starting', $progress['status']);
        $this->assertEquals(0, $progress['progress']);
    }

    public function test_import_handles_file_upload_errors(): void
    {
        // Test with invalid file type
        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->post(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file,
            'format' => 'auto',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['json_file']);
    }

    public function test_import_routes_are_properly_named(): void
    {
        $this->assertTrue(route('import.index') !== null);
        $this->assertTrue(route('import.json') !== null);
        $this->assertTrue(route('import.suno-discover') !== null);
        $this->assertTrue(route('import.suno-search') !== null);
        $this->assertTrue(route('import.suno-all') !== null);
        $this->assertTrue(route('import.progress', ['sessionId' => 'test']) !== null);
        $this->assertTrue(route('import.stats') !== null);
    }

    public function test_import_dashboard_shows_correct_navigation(): void
    {
        $response = $this->get(route('import.index'));

        $response->assertSee('href="' . route('import.index') . '"', false);
        $response->assertSee('Import Dashboard');
    }

    public function test_concurrent_imports_have_unique_session_ids(): void
    {
        Queue::fake();
        
        $file1 = UploadedFile::fake()->createWithContent('test1.json', '[]');
        $file2 = UploadedFile::fake()->createWithContent('test2.json', '[]');

        $response1 = $this->post(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file1,
            'format' => 'auto',
        ]);

        $response2 = $this->post(route('import.json'), [
            'source_type' => 'file',
            'json_file' => $file2,
            'format' => 'auto',
        ]);

        $sessionId1 = $response1->json('session_id');
        $sessionId2 = $response2->json('session_id');

        $this->assertNotEquals($sessionId1, $sessionId2);
        $this->assertStringStartsWith('import_json_', $sessionId1);
        $this->assertStringStartsWith('import_json_', $sessionId2);
    }
} 