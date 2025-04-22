<?php

namespace App\Console\Commands;

use App\Services\SimpleYouTubeUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadRandomVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload-random 
                            {--title= : Video title}
                            {--description= : Video description}
                            {--tags= : Comma-separated list of tags}
                            {--privacy=unlisted : Privacy setting (public, unlisted, private)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload a random video from the storage directory to YouTube';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get all MP4 files from the storage
        $videoFiles = Storage::disk('public')->files('videos');
        $videoFiles = array_filter($videoFiles, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'mp4';
        });
        
        if (empty($videoFiles)) {
            $this->error('No MP4 files found in the storage/app/public/videos directory');
            return 1;
        }
        
        // Select a random video
        $randomVideo = $videoFiles[array_rand($videoFiles)];
        $videoPath = storage_path('app/public/' . $randomVideo);
        $videoName = pathinfo($randomVideo, PATHINFO_FILENAME);
        
        $title = $this->option('title') ?: 'Random Upload: ' . $videoName;
        $description = $this->option('description') ?: 'Randomly selected video uploaded via SunoPanel';
        $tags = $this->option('tags') ? explode(',', $this->option('tags')) : ['auto', 'upload', 'sunopanel'];
        $privacy = $this->option('privacy');
        
        $this->info("Starting YouTube random video upload");
        $this->info("Video path: $videoPath");
        $this->info("Title: $title");
        
        if (!file_exists($videoPath)) {
            $this->error("Video file not found: $videoPath");
            return 1;
        }
        
        try {
            $uploader = new SimpleYouTubeUploader();
            
            $this->info("Uploading video...");
            $videoId = $uploader->upload(
                $videoPath,
                $title,
                $description,
                $tags,
                $privacy
            );
            
            if ($videoId) {
                $this->info("Upload successful!");
                $this->info("Video ID: $videoId");
                $this->info("YouTube URL: https://www.youtube.com/watch?v=$videoId");
                return 0;
            } else {
                $this->error("Upload failed: No video ID returned");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Upload failed with exception: " . $e->getMessage());
            Log::error("YouTube random video upload failed", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 