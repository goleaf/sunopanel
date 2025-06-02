<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ImportService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_IMPORT_LIMIT = 10000;
    private const ALLOWED_FILE_TYPES = ['application/json', 'text/plain'];
    private const VALID_DISCOVER_SECTIONS = ['trending_songs', 'new_songs', 'popular_songs'];
    private const VALID_RANK_OPTIONS = [
        'upvote_count', 'play_count', 'dislike_count', 'trending', 
        'most_recent', 'most_relevant', 'by_hour', 'by_day', 
        'by_week', 'by_month', 'all_time', 'default'
    ];

    /**
     * Validate JSON format
     */
    public function validateJsonFormat(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Detect data format automatically
     */
    public function detectFormat(string $data): string
    {
        // Try to decode as JSON first
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return 'object';
        }

        // Check for pipe delimited format
        if (str_contains($data, '|') && str_contains($data, "\n")) {
            return 'pipe';
        }

        // Default to auto
        return 'auto';
    }

    /**
     * Validate if URL is from Suno.ai domain
     */
    public function validateSunoUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }

        $allowedHosts = ['cdn1.suno.ai', 'cdn2.suno.ai', 'suno.ai'];
        return in_array($parsedUrl['host'], $allowedHosts) && 
               ($parsedUrl['scheme'] === 'https' || $parsedUrl['scheme'] === 'http');
    }

    /**
     * Sanitize track data to prevent XSS and other security issues
     */
    public function sanitizeTrackData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['audio_url', 'image_url'])) {
                // URLs should not be stripped of HTML
                $sanitized[$key] = $value;
            } else {
                // Remove script tags and their content first, then strip remaining HTML tags
                $cleaned = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', (string) $value);
                $sanitized[$key] = strip_tags($cleaned);
            }
        }

        return $sanitized;
    }

    /**
     * Parse pipe delimited data
     */
    public function parsePipeDelimitedData(string $data): array
    {
        $lines = explode("\n", trim($data));
        $parsed = [];

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 4) {
                $parsed[] = [
                    'title' => trim($parts[0]),
                    'audio_url' => trim($parts[1]),
                    'image_url' => trim($parts[2]),
                    'tags' => trim($parts[3])
                ];
            }
        }

        return $parsed;
    }

    /**
     * Validate import limits
     */
    public function validateImportLimits(int $limit, int $skip): bool
    {
        return $limit <= self::MAX_IMPORT_LIMIT && $skip >= 0;
    }

    /**
     * Create a new progress tracking session
     */
    public function createProgressSession(string $type): string
    {
        $sessionId = uniqid("import_{$type}_");
        
        $initialProgress = [
            'status' => 'starting',
            'progress' => 0,
            'message' => 'Initializing import...',
            'imported' => 0,
            'failed' => 0,
            'total' => 0,
            'created_at' => now()->toISOString()
        ];

        Cache::put("import_progress_{$sessionId}", $initialProgress, 3600);
        
        return $sessionId;
    }

    /**
     * Update progress for a session
     */
    public function updateProgress(string $sessionId, array $progressData): void
    {
        $existing = Cache::get("import_progress_{$sessionId}", []);
        $updated = array_merge($existing, $progressData);
        $updated['updated_at'] = now()->toISOString();
        
        Cache::put("import_progress_{$sessionId}", $updated, 3600);
    }

    /**
     * Validate file size
     */
    public function validateFileSize(int $sizeInBytes): bool
    {
        return $sizeInBytes <= self::MAX_FILE_SIZE;
    }

    /**
     * Validate file type
     */
    public function validateFileType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_FILE_TYPES);
    }

    /**
     * Extract Suno ID from URL
     */
    public function extractSunoId(string $url): ?string
    {
        // Pattern for CDN URLs with UUID
        if (preg_match('/\/([a-f0-9-]{36})\./', $url, $matches)) {
            return $matches[1];
        }

        // Pattern for image URLs with image_ prefix
        if (preg_match('/image_([a-f0-9-]{36})\./', $url, $matches)) {
            return $matches[1];
        }

        // Pattern for song URLs
        if (preg_match('/\/song\/([^\/\?]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Validate discover import parameters
     */
    public function validateDiscoverParams(array $params): bool
    {
        if (!isset($params['section']) || !in_array($params['section'], self::VALID_DISCOVER_SECTIONS)) {
            return false;
        }

        if (!isset($params['page_size']) || $params['page_size'] < 1 || $params['page_size'] > 100) {
            return false;
        }

        if (!isset($params['pages']) || $params['pages'] < 1 || $params['pages'] > 10) {
            return false;
        }

        return true;
    }

    /**
     * Validate search import parameters
     */
    public function validateSearchParams(array $params): bool
    {
        if (!isset($params['size']) || $params['size'] < 1 || $params['size'] > 100) {
            return false;
        }

        if (!isset($params['pages']) || $params['pages'] < 1 || $params['pages'] > 10) {
            return false;
        }

        if (!isset($params['rank_by']) || !in_array($params['rank_by'], self::VALID_RANK_OPTIONS)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate import statistics
     */
    public function calculateImportStats(array $data): array
    {
        $totalTracks = count($data);
        $validTracks = 0;
        $invalidTracks = 0;

        foreach ($data as $track) {
            if (isset($track['audio_url']) && $this->validateSunoUrl($track['audio_url'])) {
                $validTracks++;
            } else {
                $invalidTracks++;
            }
        }

        // Estimate size (rough calculation)
        $estimatedSizeMb = $totalTracks * 5; // Assume ~5MB per track

        return [
            'total_tracks' => $totalTracks,
            'valid_tracks' => $validTracks,
            'invalid_tracks' => $invalidTracks,
            'estimated_size_mb' => $estimatedSizeMb
        ];
    }

    /**
     * Filter out duplicate tracks
     */
    public function filterDuplicates(array $newTracks, array $existingUrls): array
    {
        $newTracksList = [];
        $duplicates = [];

        foreach ($newTracks as $track) {
            if (in_array($track['audio_url'], $existingUrls)) {
                $duplicates[] = $track;
            } else {
                $newTracksList[] = $track;
            }
        }

        return [
            'new_tracks' => $newTracksList,
            'duplicates' => $duplicates
        ];
    }

    /**
     * Validate batch size
     */
    public function validateBatchSize(array $batch): bool
    {
        return count($batch) <= self::MAX_IMPORT_LIMIT;
    }

    /**
     * Generate import summary
     */
    public function generateImportSummary(string $sessionId, array $results): array
    {
        return [
            'session_id' => $sessionId,
            'imported' => $results['imported'] ?? 0,
            'failed' => $results['failed'] ?? 0,
            'duplicates' => $results['duplicates'] ?? 0,
            'total_processed' => $results['total_processed'] ?? 0,
            'completion_time' => now()->toISOString(),
            'success_rate' => $this->calculateSuccessRate($results)
        ];
    }

    /**
     * Calculate success rate
     */
    private function calculateSuccessRate(array $results): float
    {
        $total = $results['total_processed'] ?? 0;
        $imported = $results['imported'] ?? 0;

        if ($total === 0) {
            return 0.0;
        }

        return round(($imported / $total) * 100, 2);
    }

    /**
     * Validate import request data
     */
    public function validateImportRequest(array $data, string $type): array
    {
        $errors = [];

        switch ($type) {
            case 'json':
                if (empty($data['source_type'])) {
                    $errors[] = 'Source type is required';
                }
                
                if ($data['source_type'] === 'file' && empty($data['json_file'])) {
                    $errors[] = 'JSON file is required when source type is file';
                }
                
                if ($data['source_type'] === 'url' && empty($data['json_url'])) {
                    $errors[] = 'JSON URL is required when source type is url';
                }
                break;

            case 'discover':
                if (!$this->validateDiscoverParams($data)) {
                    $errors[] = 'Invalid discover parameters';
                }
                break;

            case 'search':
                if (!$this->validateSearchParams($data)) {
                    $errors[] = 'Invalid search parameters';
                }
                break;
        }

        return $errors;
    }

    /**
     * Log import activity
     */
    public function logImportActivity(string $sessionId, string $action, array $context = []): void
    {
        Log::info("Import activity: {$action}", [
            'session_id' => $sessionId,
            'action' => $action,
            'context' => $context,
            'timestamp' => now()->toISOString()
        ]);
    }
} 