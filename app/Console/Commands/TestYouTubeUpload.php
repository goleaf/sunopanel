<?php

namespace App\Console\Commands;

use App\Services\SimpleYouTubeUploader;
use App\Services\YouTubeUploader;
use App\Jobs\UploadTrackToYouTube;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class TestYouTubeUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:test-upload 
                            {--file= : Path to video file to upload}
                            {--title= : Video title}
                            {--description= : Video description}
                            {--tags= : Comma-separated list of tags}
                            {--privacy=unlisted : Privacy status (public, unlisted, private)}
                            {--category=Music : Video category}
                            {--oauth : Force using OAuth uploader}
                            {--simple : Force using simple uploader}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test uploading a video to YouTube';

    /**
     * The YouTube uploader instances.
     */
    private SimpleYouTubeUploader $simpleUploader;
    private YouTubeUploader $oauthUploader;

    /**
     * Create a new command instance.
     */
    public function __construct(SimpleYouTubeUploader $simpleUploader, YouTubeUploader $oauthUploader)
    {
        parent::__construct();
        $this->simpleUploader = $simpleUploader;
        $this->oauthUploader = $oauthUploader;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->option('file');
        if (empty($file)) {
            $this->error('Video file path is required.');
            return 1;
        }

        // Check if file exists
        if (!file_exists($file)) {
            $this->error("Video file not found: {$file}");
            return 1;
        }

        // Get upload options
        $title = $this->option('title') ?? 'Test Upload ' . date('Y-m-d H:i:s');
        $description = $this->option('description') ?? 'This is a test upload from the SunoPanel YouTube uploader.';
        $tags = $this->option('tags') ? explode(',', $this->option('tags')) : ['test', 'sunopanel'];
        $privacy = $this->option('privacy');
        $category = $this->option('category');

        // Display upload settings
        $this->info('Video Upload Configuration:');
        $this->line("File:        {$file}");
        $this->line("Title:       {$title}");
        $this->line("Description: {$description}");
        $this->line("Tags:        " . implode(', ', $tags));
        $this->line("Privacy:     {$privacy}");
        $this->line("Category:    {$category}");

        try {
            // Determine which uploader to use
            if ($this->option('oauth')) {
                $this->info('Using OAuth YouTubeUploader...');
                
                // Check if authenticated
                if (!$this->oauthUploader->isAuthenticated()) {
                    $authUrl = $this->oauthUploader->getAuthUrl();
                    $this->warn('Not authenticated. Please visit this URL to authenticate:');
                    $this->line($authUrl);
                    $this->warn('Then add the received credentials to your .env file:');
                    $this->line('YOUTUBE_ACCESS_TOKEN=your_access_token');
                    $this->line('YOUTUBE_REFRESH_TOKEN=your_refresh_token');
                    $this->line('YOUTUBE_TOKEN_EXPIRES_AT=expiry_timestamp');
                    return 1;
                }
                
                $videoId = $this->oauthUploader->upload(
                    $file,
                    $title,
                    $description,
                    $tags,
                    $privacy,
                    is_numeric($category) ? $category : null
                );
            } else {
                $this->info('Using SimpleYouTubeUploader...');
                $videoId = $this->simpleUploader->upload(
                    $file,
                    $title,
                    $description,
                    $tags,
                    $privacy,
                    $category
                );
            }

            if ($videoId) {
                $this->info('Upload successful!');
                $this->info("Video ID: {$videoId}");
                $this->info("Video URL: https://www.youtube.com/watch?v={$videoId}");
                return 0;
            } else {
                $this->error('Upload failed: No video ID returned.');
                return 1;
            }
        } catch (Exception $e) {
            $this->error('Upload failed: ' . $e->getMessage());
            Log::error('YouTube upload test failed: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Get or create a test video file.
     *
     * @return string|null Path to the test video
     */
    protected function getTestVideo()
    {
        $testDir = storage_path('app/public/test');
        File::ensureDirectoryExists($testDir);
        
        $testVideoPath = $testDir . '/test-video.mp4';
        
        // Check if test video already exists
        if (File::exists($testVideoPath) && filesize($testVideoPath) > 0) {
            $this->info('Using existing test video.');
            return $testVideoPath;
        }
        
        $this->info('Creating test video...');
        
        // Check if ffmpeg is available
        $process = Process::fromShellCommandline('which ffmpeg');
        $process->run();
        
        if (!$process->isSuccessful()) {
            $this->warn('ffmpeg not found. Trying to create a simple test file.');
            
            // Create a simple binary file if ffmpeg is not available
            $data = str_repeat('0123456789ABCDEF', 1024 * 10); // ~160KB
            File::put($testVideoPath, $data);
            
            $this->warn('Created a dummy file instead. This will not actually work as a video.');
            return $testVideoPath;
        }
        
        // Create a 5-second test video with ffmpeg
        $process = Process::fromShellCommandline(
            'ffmpeg -f lavfi -i color=c=blue:s=320x240:d=5 -vf "drawtext=text=\'Test Video\':fontcolor=white:fontsize=24:x=(w-text_w)/2:y=(h-text_h)/2" -c:v libx264 -tune stillimage -pix_fmt yuv420p ' . $testVideoPath
        );
        
        $this->info('Running: ' . $process->getCommandLine());
        
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->warn($buffer);
            } else {
                $this->line($buffer);
            }
        });
        
        if (!$process->isSuccessful()) {
            $this->error('Failed to create test video with ffmpeg.');
            return null;
        }
        
        $this->info('Test video created successfully.');
        return $testVideoPath;
    }
    
    /**
     * Check if the OAuth uploader has all requirements.
     */
    protected function checkOAuthUploaderRequirements()
    {
        $this->info('Checking OAuth uploader requirements...');
        
        // Check for client ID and client secret
        $clientId = config('youtube.client_id');
        $clientSecret = config('youtube.client_secret');
        
        if (empty($clientId) || empty($clientSecret)) {
            $this->warn('OAuth client ID or client secret is not set in config/youtube.php');
        } else {
            $this->info('Client ID and secret are configured.');
        }
        
        // Check for access token
        $accessToken = config('youtube.access_token');
        if (empty($accessToken)) {
            $this->warn('No access token found. OAuth authentication may fail.');
        } else {
            $this->info('Access token is configured.');
        }
    }
    
    /**
     * Check if the simple uploader has all requirements.
     */
    protected function checkSimpleUploaderRequirements()
    {
        $this->info('Checking simple uploader requirements...');
        
        // Check for email and password
        $email = config('youtube.simple_uploader.email');
        $password = config('youtube.simple_uploader.password');
        
        if (empty($email) || empty($password)) {
            $this->warn('Simple uploader email or password is not set in config/youtube.php');
        } else {
            $this->info('Email and password are configured.');
        }
        
        // Check for uploader script
        $process = Process::fromShellCommandline('which youtube-direct-upload');
        $process->run();
        
        if (!$process->isSuccessful()) {
            $this->warn('youtube-direct-upload script not found in PATH.');
            
            // Check in vendor/bin
            $vendorScript = base_path('vendor/bin/youtube-direct-upload');
            if (File::exists($vendorScript)) {
                $this->info('Found script at: ' . $vendorScript);
            } else {
                $this->warn('Could not find youtube-direct-upload script.');
            }
        } else {
            $this->info('youtube-direct-upload script found at: ' . trim($process->getOutput()));
        }
    }
} 