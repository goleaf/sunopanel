<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ImportServiceTest extends TestCase
{
    private ImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = new ImportService();
        Storage::fake('local');
        Cache::flush();
    }

    public function test_validates_json_format_correctly(): void
    {
        $validJson = '{"tracks": [{"title": "Test", "audio_url": "https://example.com/test.mp3"}]}';
        $invalidJson = '{"tracks": [{"title": "Test"'; // Invalid JSON

        $this->assertTrue($this->importService->validateJsonFormat($validJson));
        $this->assertFalse($this->importService->validateJsonFormat($invalidJson));
    }

    public function test_detects_pipe_delimited_format(): void
    {
        $pipeData = "Track 1|https://cdn1.suno.ai/track1.mp3|https://cdn2.suno.ai/track1.jpg|electronic\nTrack 2|https://cdn1.suno.ai/track2.mp3|https://cdn2.suno.ai/track2.jpg|jazz";
        
        $format = $this->importService->detectFormat($pipeData);
        $this->assertEquals('pipe', $format);
    }

    public function test_detects_json_object_format(): void
    {
        $jsonData = json_encode([
            ['title' => 'Track 1', 'audio_url' => 'https://example.com/track1.mp3'],
            ['title' => 'Track 2', 'audio_url' => 'https://example.com/track2.mp3']
        ]);
        
        $format = $this->importService->detectFormat($jsonData);
        $this->assertEquals('object', $format);
    }

    public function test_validates_suno_urls(): void
    {
        $validUrls = [
            'https://cdn1.suno.ai/track.mp3',
            'https://cdn2.suno.ai/image.jpg',
            'https://suno.ai/song/123'
        ];

        $invalidUrls = [
            'https://example.com/track.mp3',
            'http://malicious-site.com/file.mp3',
            'ftp://example.com/file.mp3'
        ];

        foreach ($validUrls as $url) {
            $this->assertTrue($this->importService->validateSunoUrl($url));
        }

        foreach ($invalidUrls as $url) {
            $this->assertFalse($this->importService->validateSunoUrl($url));
        }
    }

    public function test_sanitizes_track_data(): void
    {
        $rawData = [
            'title' => '<script>alert("xss")</script>Track Title',
            'audio_url' => 'https://cdn1.suno.ai/track.mp3',
            'image_url' => 'https://cdn2.suno.ai/image.jpg',
            'tags' => 'electronic, <script>alert("xss")</script>ambient'
        ];

        $sanitized = $this->importService->sanitizeTrackData($rawData);

        $this->assertEquals('Track Title', $sanitized['title']);
        $this->assertEquals('electronic, ambient', $sanitized['tags']);
        $this->assertEquals($rawData['audio_url'], $sanitized['audio_url']);
        $this->assertEquals($rawData['image_url'], $sanitized['image_url']);
    }

    public function test_parses_pipe_delimited_data(): void
    {
        $pipeData = "Track 1|https://cdn1.suno.ai/track1.mp3|https://cdn2.suno.ai/track1.jpg|electronic\nTrack 2|https://cdn1.suno.ai/track2.mp3|https://cdn2.suno.ai/track2.jpg|jazz";
        
        $parsed = $this->importService->parsePipeDelimitedData($pipeData);

        $this->assertCount(2, $parsed);
        $this->assertEquals('Track 1', $parsed[0]['title']);
        $this->assertEquals('https://cdn1.suno.ai/track1.mp3', $parsed[0]['audio_url']);
        $this->assertEquals('electronic', $parsed[0]['tags']);
    }

    public function test_validates_import_limits(): void
    {
        $this->assertTrue($this->importService->validateImportLimits(100, 0));
        $this->assertTrue($this->importService->validateImportLimits(1000, 50));
        
        $this->assertFalse($this->importService->validateImportLimits(10001, 0)); // Over limit
        $this->assertFalse($this->importService->validateImportLimits(100, -1)); // Negative skip
    }

    public function test_creates_progress_session(): void
    {
        $sessionId = $this->importService->createProgressSession('json');
        
        $this->assertStringStartsWith('import_json_', $sessionId);
        
        $progress = Cache::get("import_progress_{$sessionId}");
        $this->assertNotNull($progress);
        $this->assertEquals('starting', $progress['status']);
        $this->assertEquals(0, $progress['progress']);
    }

    public function test_updates_progress_session(): void
    {
        $sessionId = $this->importService->createProgressSession('test');
        
        $this->importService->updateProgress($sessionId, [
            'status' => 'running',
            'progress' => 50,
            'message' => 'Processing...',
            'imported' => 25,
            'failed' => 2,
            'total' => 50
        ]);

        $progress = Cache::get("import_progress_{$sessionId}");
        $this->assertEquals('running', $progress['status']);
        $this->assertEquals(50, $progress['progress']);
        $this->assertEquals(25, $progress['imported']);
    }

    public function test_validates_file_size_limits(): void
    {
        $this->assertTrue($this->importService->validateFileSize(1024 * 1024)); // 1MB
        $this->assertTrue($this->importService->validateFileSize(10 * 1024 * 1024)); // 10MB
        
        $this->assertFalse($this->importService->validateFileSize(50 * 1024 * 1024)); // 50MB - over limit
    }

    public function test_validates_supported_file_types(): void
    {
        $this->assertTrue($this->importService->validateFileType('application/json'));
        $this->assertTrue($this->importService->validateFileType('text/plain'));
        
        $this->assertFalse($this->importService->validateFileType('application/pdf'));
        $this->assertFalse($this->importService->validateFileType('image/jpeg'));
    }

    public function test_extracts_suno_id_from_url(): void
    {
        $urls = [
            'https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3' => '69c0d3c4-a06f-471e-a396-4cb09c9ec2b6',
            'https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg' => 'a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe',
            'https://suno.ai/song/12345' => '12345'
        ];

        foreach ($urls as $url => $expectedId) {
            $extractedId = $this->importService->extractSunoId($url);
            $this->assertEquals($expectedId, $extractedId);
        }
    }

    public function test_validates_import_source_parameters(): void
    {
        // Valid discover parameters
        $this->assertTrue($this->importService->validateDiscoverParams([
            'section' => 'trending_songs',
            'page_size' => 20,
            'pages' => 2
        ]));

        // Invalid discover parameters
        $this->assertFalse($this->importService->validateDiscoverParams([
            'section' => 'invalid_section',
            'page_size' => 20,
            'pages' => 2
        ]));

        // Valid search parameters
        $this->assertTrue($this->importService->validateSearchParams([
            'size' => 25,
            'pages' => 3,
            'rank_by' => 'upvote_count'
        ]));

        // Invalid search parameters
        $this->assertFalse($this->importService->validateSearchParams([
            'size' => 0,
            'pages' => 3,
            'rank_by' => 'upvote_count'
        ]));
    }

    public function test_calculates_import_statistics(): void
    {
        $data = [
            ['title' => 'Track 1', 'audio_url' => 'https://cdn1.suno.ai/track1.mp3'],
            ['title' => 'Track 2', 'audio_url' => 'https://cdn1.suno.ai/track2.mp3'],
            ['title' => 'Track 3', 'audio_url' => 'https://cdn1.suno.ai/track3.mp3']
        ];

        $stats = $this->importService->calculateImportStats($data);

        $this->assertEquals(3, $stats['total_tracks']);
        $this->assertEquals(3, $stats['valid_tracks']);
        $this->assertEquals(0, $stats['invalid_tracks']);
        $this->assertGreaterThan(0, $stats['estimated_size_mb']);
    }

    public function test_handles_duplicate_detection(): void
    {
        $existingTracks = [
            'https://cdn1.suno.ai/existing1.mp3',
            'https://cdn1.suno.ai/existing2.mp3'
        ];

        $newTracks = [
            ['audio_url' => 'https://cdn1.suno.ai/existing1.mp3', 'title' => 'Duplicate'],
            ['audio_url' => 'https://cdn1.suno.ai/new1.mp3', 'title' => 'New Track'],
            ['audio_url' => 'https://cdn1.suno.ai/existing2.mp3', 'title' => 'Another Duplicate']
        ];

        $result = $this->importService->filterDuplicates($newTracks, $existingTracks);

        $this->assertCount(1, $result['new_tracks']);
        $this->assertCount(2, $result['duplicates']);
        $this->assertEquals('New Track', $result['new_tracks'][0]['title']);
    }

    public function test_validates_batch_import_size(): void
    {
        $smallBatch = array_fill(0, 100, ['title' => 'Track']);
        $largeBatch = array_fill(0, 10001, ['title' => 'Track']);

        $this->assertTrue($this->importService->validateBatchSize($smallBatch));
        $this->assertFalse($this->importService->validateBatchSize($largeBatch));
    }

    public function test_generates_import_summary(): void
    {
        $sessionId = 'test_session_123';
        $results = [
            'imported' => 50,
            'failed' => 5,
            'duplicates' => 10,
            'total_processed' => 65
        ];

        $summary = $this->importService->generateImportSummary($sessionId, $results);

        $this->assertEquals($sessionId, $summary['session_id']);
        $this->assertEquals(50, $summary['imported']);
        $this->assertEquals(5, $summary['failed']);
        $this->assertEquals(10, $summary['duplicates']);
        $this->assertArrayHasKey('completion_time', $summary);
    }
} 