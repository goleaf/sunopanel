<?php

namespace App\Console\Commands;

use App\Models\Track;
use App\Services\SimpleYouTubeUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SimpleYouTubeUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:simple-upload 
                          {track_id? : ID of track to upload}
                          {--all : Upload all eligible tracks}
                          {--privacy=public : Privacy setting (public, unlisted, private)}
                          {--shorts : Upload as YouTube Shorts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simple YouTube uploader for single track or all eligible tracks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get uploader service
        $uploader = app(SimpleYouTubeUploader::class);
        
        if (!$uploader->isAuthenticated()) {
            $this->error('YouTube service is not authenticated. Please authenticate first.');
            return 1;
        }
        
        // Get privacy setting
        $privacy = $this->option('privacy');
        $isShort = $this->option('shorts');
        
        if ($isShort) {
            $this->info('Videos will be uploaded as YouTube Shorts');
        } else {
            $this->info('Videos will be uploaded as standard YouTube videos');
        }
        
        // Check what mode we're running in
        $uploadAll = $this->option('all');
        $trackId = $this->argument('track_id');
        
        if ($uploadAll) {
            return $this->uploadAllTracks($uploader, $privacy, $isShort);
        } elseif ($trackId) {
            return $this->uploadSingleTrack($uploader, $trackId, $privacy, $isShort);
        } else {
            $this->error('Please specify a track ID or use --all option.');
            return 1;
        }
    }
    
    /**
     * Upload a single track
     */
    protected function uploadSingleTrack(SimpleYouTubeUploader $uploader, int $trackId, string $privacy, bool $isShort)
    {
        try {
            $track = Track::findOrFail($trackId);
            
            $videoType = $isShort ? 'YouTube Shorts' : 'YouTube video';
            $this->info("Uploading track: {$track->title} (ID: {$trackId}) as {$videoType}");
            
            if ($track->status !== 'completed') {
                $this->error("Track is not completed (status: {$track->status})");
                return 1;
            }
            
            if (empty($track->mp4_path)) {
                $this->error("Track does not have an MP4 file");
                return 1;
            }
            
            // Upload the track
            $videoId = $uploader->uploadTrack(
                $track,
                null,    // Use default title
                null,    // Use default description
                $privacy,
                true,    // Add to playlists
                $isShort // Upload as Short if requested
            );
            
            $this->info("Track uploaded successfully to {$videoType}!");
            $this->info("YouTube URL: https://www.youtube.com/watch?v={$videoId}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error uploading track: " . $e->getMessage());
            Log::error("Error uploading track {$trackId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Upload all eligible tracks
     */
    protected function uploadAllTracks(SimpleYouTubeUploader $uploader, string $privacy, bool $isShort)
    {
        // Get all eligible tracks
        $tracks = Track::where('status', 'completed')
            ->whereNotNull('mp4_path')
            ->whereNull('youtube_video_id')
            ->get();
            
        $total = $tracks->count();
        
        if ($total === 0) {
            $this->info("No eligible tracks found for upload.");
            return 0;
        }
        
        $videoType = $isShort ? 'YouTube Shorts' : 'YouTube videos';
        $this->info("Found {$total} eligible tracks for upload as {$videoType}.");
        
        $successCount = 0;
        $failedTracks = [];
        
        // Create progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($tracks as $track) {
            try {
                $videoId = $uploader->uploadTrack(
                    $track,
                    null,    // Use default title
                    null,    // Use default description
                    $privacy,
                    true,    // Add to playlists if not a Short
                    $isShort // Upload as Short if requested
                );
                
                if ($videoId) {
                    $successCount++;
                }
                
                // Small delay to avoid rate limits
                usleep(500000); // 0.5 second delay
            } catch (\Exception $e) {
                Log::error("Failed to upload track {$track->id}: " . $e->getMessage());
                $failedTracks[] = $track->id;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Upload summary:");
        $this->info("- Total tracks: {$total}");
        $this->info("- Successfully uploaded: {$successCount}");
        $this->info("- Failed: " . count($failedTracks));
        
        if (!empty($failedTracks)) {
            $this->warn("Failed track IDs: " . implode(', ', $failedTracks));
        }
        
        return count($failedTracks) > 0 ? 1 : 0;
    }
} 