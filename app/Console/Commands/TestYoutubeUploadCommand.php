<?php

namespace App\Console\Commands;

use App\Services\SimpleYouTubeUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestYoutubeUploadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:test-upload {file? : Path to video file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test YouTube upload functionality';

    /**
     * Execute the console command.
     */
    public function handle(SimpleYouTubeUploader $uploader)
    {
        $filePath = $this->argument('file');
        
        if (!$filePath) {
            // Get all video files from storage
            $files = Storage::disk('public')->files('videos');
            
            if (empty($files)) {
                $this->error('No video files found in storage/app/public/videos directory.');
                return 1;
            }
            
            // Get the first video file
            $filePath = storage_path('app/public/' . $files[0]);
            $this->info("Using first available video: $filePath");
        }
        
        if (!file_exists($filePath)) {
            $this->error("File does not exist: $filePath");
            return 1;
        }
        
        $this->info("Testing YouTube upload with file: $filePath");
        
        try {
            $videoId = $uploader->upload(
                $filePath,
                'Test Upload ' . date('Y-m-d H:i:s'),
                'This is a test upload from Laravel command',
                ['test', 'upload', 'laravel'],
                'private',
                'Music'
            );
            
            if ($videoId) {
                $this->info("Upload successful! YouTube Video ID: $videoId");
                return 0;
            } else {
                $this->error("Upload failed. No video ID returned.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Upload failed with exception: " . $e->getMessage());
            Log::error("YouTube upload test failed", [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 