<?php

namespace App\Console\Commands;

use App\Services\SimpleYouTubeUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestYouTubeUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:test-upload 
                            {video_path : Path to the video file to upload}
                            {--title= : Video title}
                            {--description= : Video description}
                            {--tags= : Comma-separated list of tags}
                            {--privacy=unlisted : Privacy setting (public, unlisted, private)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test YouTube video upload';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $videoPath = $this->argument('video_path');
        $title = $this->option('title') ?: 'Test Upload ' . date('Y-m-d H:i:s');
        $description = $this->option('description') ?: 'Uploaded via SunoPanel';
        $tags = $this->option('tags') ? explode(',', $this->option('tags')) : [];
        $privacy = $this->option('privacy');
        
        $this->info("Starting YouTube test upload");
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
            Log::error("YouTube test upload failed", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 