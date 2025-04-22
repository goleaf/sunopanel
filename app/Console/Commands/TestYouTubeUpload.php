<?php

namespace App\Console\Commands;

use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

final class TestYouTubeUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:test-upload {track_id? : The ID of the track to upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test YouTube upload functionality with a specific track';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $trackId = $this->argument('track_id');
        
        if (!$trackId) {
            // If no track ID provided, get the first track with a video file
            $track = Track::whereNotNull('mp4_file')
                ->where('mp4_file', '!=', '')
                ->first();
                
            if (!$track) {
                $this->error('No tracks with video files found in the database.');
                return 1;
            }
        } else {
            // Get the specified track
            $track = Track::find($trackId);
            
            if (!$track) {
                $this->error("Track with ID {$trackId} not found.");
                return 1;
            }
            
            if (empty($track->mp4_file)) {
                $this->error("Track with ID {$trackId} doesn't have an MP4 file associated with it.");
                return 1;
            }
        }
        
        $this->info("Testing YouTube upload with track: {$track->title} (ID: {$track->id})");
        
        $videoPath = storage_path('app/public/videos/' . $track->mp4_file);
        if (!file_exists($videoPath)) {
            $this->error("Video file not found at path: {$videoPath}");
            return 1;
        }
        
        $this->info("Video file exists at: {$videoPath}");
        
        try {
            $this->info('Executing YouTube upload command...');
            
            // Use the direct upload command
            $exitCode = Artisan::call('youtube:upload', [
                '--track_id' => $track->id,
                '--title' => '[TEST] ' . $track->title,
                '--description' => 'This is a test upload from SunoPanel',
                '--privacy' => 'unlisted'
            ]);
            
            if ($exitCode !== 0) {
                $output = Artisan::output();
                $this->error('YouTube upload command failed: ' . $output);
                return 1;
            }
            
            $this->info('Upload process completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Exception in TestYouTubeUpload command', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 