<?php

namespace App\Console\Commands;

use App\Services\SimpleYouTubeUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class UploadTrackToYouTube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload {file : Path to MP4 file} {title? : Video title} {description? : Video description} {tags? : Comma-separated tags} {privacy=unlisted : Privacy setting (public, unlisted, private)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload an MP4 file to YouTube';

    /**
     * @var SimpleYouTubeUploader
     */
    protected $uploader;

    /**
     * Create a new command instance.
     *
     * @param SimpleYouTubeUploader $uploader
     */
    public function __construct(SimpleYouTubeUploader $uploader)
    {
        parent::__construct();
        $this->uploader = $uploader;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        
        // Check if file exists
        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }
        
        // Check if file is an MP4
        if (strtolower(File::extension($filePath)) !== 'mp4') {
            $this->error("File must be an MP4: {$filePath}");
            return 1;
        }
        
        // Get title from argument or filename
        $title = $this->argument('title') ?: File::name($filePath);
        
        // Get description and tags
        $description = $this->argument('description') ?: '';
        $tags = $this->argument('tags') ?: '';
        $privacy = $this->argument('privacy');
        
        $this->info("Uploading {$filePath} to YouTube...");
        $this->info("Title: {$title}");
        $this->info("Privacy: {$privacy}");
        
        try {
            $videoId = $this->uploader->upload(
                $filePath,
                $title,
                $description,
                $tags,
                $privacy,
                'Music'
            );
            
            if ($videoId) {
                $this->info("Upload successful! Video ID: {$videoId}");
                $this->info("Video URL: https://www.youtube.com/watch?v={$videoId}");
                return 0;
            }
            
            $this->error("Upload failed. No video ID returned.");
            return 1;
        } catch (\Exception $e) {
            $this->error("Upload failed: " . $e->getMessage());
            Log::error("YouTube upload failed: " . $e->getMessage(), [
                'file' => $filePath,
                'exception' => $e,
            ]);
            return 1;
        }
    }
} 