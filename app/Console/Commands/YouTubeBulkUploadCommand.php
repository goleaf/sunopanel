<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Track;
use App\Services\YouTubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class YouTubeBulkUploadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:bulk-upload 
                            {--status=pending : Upload tracks with this status}
                            {--limit=10 : Maximum number of tracks to upload}
                            {--privacy=unlisted : Privacy status for uploaded videos}
                            {--playlist= : Add videos to this playlist ID}
                            {--retry=3 : Number of retry attempts for failed uploads}
                            {--dry-run : Show what would be uploaded without actually uploading}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk upload tracks to YouTube with progress tracking and error handling';

    /**
     * Execute the console command.
     */
    public function handle(YouTubeService $youtubeService): int
    {
        $status = $this->option('status');
        $limit = (int) $this->option('limit');
        $privacy = $this->option('privacy');
        $playlistId = $this->option('playlist');
        $maxRetries = (int) $this->option('retry');
        $dryRun = $this->option('dry-run');

        $this->info("YouTube Bulk Upload Command");
        $this->info("Status filter: {$status}");
        $this->info("Limit: {$limit}");
        $this->info("Privacy: {$privacy}");
        
        if ($playlistId) {
            $this->info("Playlist: {$playlistId}");
        }
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual uploads will be performed");
        }

        // Check authentication
        if (!$dryRun && !$youtubeService->isAuthenticated()) {
            $this->error('YouTube API not authenticated. Please authenticate first.');
            return 1;
        }

        // Get tracks to upload
        $query = Track::query();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // Only get tracks that haven't been uploaded to YouTube yet
        $query->whereNull('youtube_video_id');
        
        $tracks = $query->limit($limit)->get();

        if ($tracks->isEmpty()) {
            $this->info('No tracks found matching the criteria.');
            return 0;
        }

        $this->info("Found {$tracks->count()} tracks to upload");

        if ($dryRun) {
            $this->table(
                ['ID', 'Title', 'Status', 'File Path'],
                $tracks->map(fn($track) => [
                    $track->id,
                    $track->title,
                    $track->status,
                    $track->file_path
                ])->toArray()
            );
            return 0;
        }

        // Prepare video data for bulk upload
        $videos = [];
        foreach ($tracks as $track) {
            $filePath = $track->mp4_file_path;
            
            if (!$filePath || !file_exists($filePath)) {
                $this->warn("File not found for track {$track->id}: " . ($filePath ?? 'Track has no mp4_path'));
                continue;
            }

            $videos[] = [
                'path' => $filePath,
                'title' => $track->title,
                'description' => $this->generateDescription($track),
                'tags' => $this->generateTags($track),
                'privacyStatus' => $privacy,
                'madeForKids' => false,
                'isShort' => false,
                'categoryId' => '10', // Music category
                'playlistId' => $playlistId,
                'track_id' => $track->id,
            ];
        }

        if (empty($videos)) {
            $this->error('No valid video files found for upload.');
            return 1;
        }

        $this->info("Starting bulk upload of " . count($videos) . " videos...");

        // Create progress bar
        $progressBar = $this->output->createProgressBar(count($videos));
        $progressBar->setFormat('verbose');

        // Progress callback
        $progressCallback = function($current, $total, $status, $title) use ($progressBar) {
            $progressBar->setProgress($current);
            $progressBar->setMessage("[$status] $title");
            $progressBar->display();
        };

        // Perform bulk upload
        $results = $youtubeService->bulkUploadVideos($videos, $progressCallback);

        $progressBar->finish();
        $this->newLine(2);

        // Process results
        $successful = [];
        $failed = [];

        foreach ($results as $index => $result) {
            $track = $tracks->where('id', $videos[$index]['track_id'])->first();
            
            if ($result['success']) {
                $successful[] = $result;
                
                // Update track with YouTube video ID
                if ($track) {
                    $track->youtube_video_id = $result['video_id'];
                    $track->status = 'uploadedToYoutube';
                    $track->save();
                }
                
                $this->info("✓ {$result['title']} -> {$result['video_id']}");
            } else {
                $failed[] = array_merge($result, ['track_id' => $videos[$index]['track_id']]);
                $this->error("✗ {$result['title']}: {$result['error']}");
            }
        }

        // Retry failed uploads if requested
        if (!empty($failed) && $maxRetries > 0) {
            $this->newLine();
            $this->info("Retrying " . count($failed) . " failed uploads...");

            $retryVideos = array_map(function($failedResult) use ($videos) {
                $originalIndex = array_search($failedResult['track_id'], array_column($videos, 'track_id'));
                return $videos[$originalIndex];
            }, $failed);

            $retryResults = $youtubeService->retryFailedUploads($retryVideos, $maxRetries, $progressCallback);

            foreach ($retryResults as $result) {
                if ($result['success']) {
                    $track = $tracks->where('id', $result['track_id'] ?? null)->first();
                    
                    if ($track) {
                        $track->youtube_video_id = $result['video_id'];
                        $track->status = 'uploadedToYoutube';
                        $track->save();
                    }
                    
                    $this->info("✓ Retry successful: {$result['title']} -> {$result['video_id']}");
                    
                    // Move from failed to successful
                    $successful[] = $result;
                    $failed = array_filter($failed, fn($f) => $f['title'] !== $result['title']);
                } else {
                    $this->error("✗ Retry failed: {$result['title']}: {$result['error']}");
                }
            }
        }

        // Summary
        $this->newLine();
        $this->info("Upload Summary:");
        $this->info("✓ Successful: " . count($successful));
        $this->info("✗ Failed: " . count($failed));

        if (!empty($failed)) {
            $this->newLine();
            $this->warn("Failed uploads:");
            foreach ($failed as $failure) {
                $this->line("  - {$failure['title']}: {$failure['error']}");
            }
        }

        return count($failed) > 0 ? 1 : 0;
    }

    /**
     * Generate description for the video
     */
    private function generateDescription(Track $track): string
    {
        $description = $track->title;
        
        if ($track->genre) {
            $description .= "\n\nGenre: " . $track->genre->name;
        }
        
        if ($track->description) {
            $description .= "\n\n" . $track->description;
        }
        
        $description .= "\n\nUploaded via SunoPanel";
        
        return $description;
    }

    /**
     * Generate tags for the video
     */
    private function generateTags(Track $track): array
    {
        $tags = ['music', 'audio'];
        
        if ($track->genre) {
            $tags[] = strtolower($track->genre->name);
            $tags[] = strtolower(str_replace(' ', '', $track->genre->name));
        }
        
        // Add title words as tags (limit to reasonable length)
        $titleWords = explode(' ', strtolower($track->title));
        $titleWords = array_filter($titleWords, fn($word) => strlen($word) > 2);
        $titleWords = array_slice($titleWords, 0, 5); // Limit to 5 words
        
        $tags = array_merge($tags, $titleWords);
        
        return array_unique($tags);
    }
} 