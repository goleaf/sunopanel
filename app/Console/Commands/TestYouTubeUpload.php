<?php

namespace App\Console\Commands;

use App\Services\SimpleYouTubeUploader;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

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
                            {--category=Music : Video category}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test uploading a video to YouTube with username/password';

    /**
     * The YouTube uploader instance.
     */
    private SimpleYouTubeUploader $uploader;

    /**
     * Create a new command instance.
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
        $file = $this->option('file');
        
        // Get a test file if not provided
        if (empty($file)) {
            $this->info('No video file specified, using a test video...');
            $file = $this->getTestVideo();
            
            if (!$file) {
                $this->error('Failed to create test video. Please specify a file with --file option.');
                return 1;
            }
        }

        // Check if file exists
        if (!file_exists($file)) {
            $this->error("Video file not found: {$file}");
            return 1;
        }

        // Get upload options
        $title = $this->option('title') ?? 'Test Upload ' . date('Y-m-d H:i:s');
        $description = $this->option('description') ?? 'This is a test upload from the SunoPanel direct YouTube uploader.';
        $tags = $this->option('tags') ? explode(',', $this->option('tags')) : ['test', 'sunopanel'];
        $privacy = $this->option('privacy');
        $category = $this->option('category');

        // Check credentials
        $email = Config::get('youtube.email');
        $password = Config::get('youtube.password');
        
        if (empty($email) || empty($password)) {
            $this->error('YouTube email or password not configured! Please set the following in your .env file:');
            $this->line('YOUTUBE_EMAIL=youremail@example.com');
            $this->line('YOUTUBE_PASSWORD=yourpassword');
            return 1;
        }
        
        // Display upload settings
        $this->info('Video Upload Configuration:');
        $this->line("File:        {$file}");
        $this->line("Title:       {$title}");
        $this->line("Description: {$description}");
        $this->line("Tags:        " . implode(', ', $tags));
        $this->line("Privacy:     {$privacy}");
        $this->line("Category:    {$category}");
        $this->line("Email:       {$email}");
        $this->line("Password:    " . str_repeat('*', strlen($password)));
        
        // Check uploader script
        $scriptPath = '/usr/local/bin/youtube-direct-upload';
        if (!file_exists($scriptPath)) {
            $scriptPath = storage_path('app/scripts/youtube-direct-upload');
            if (!file_exists($scriptPath)) {
                $this->error("YouTube uploader script not found at:");
                $this->line("- /usr/local/bin/youtube-direct-upload");
                $this->line("- " . storage_path('app/scripts/youtube-direct-upload'));
                return 1;
            }
        }
        
        $this->info("Using uploader script: {$scriptPath}");
        
        // Check Python and Selenium
        $this->info("Checking dependencies...");
        $pythonCheck = shell_exec('which python3 2>&1');
        if (empty($pythonCheck)) {
            $this->error("Python 3 is not installed or not in the PATH. Please install Python 3.");
            return 1;
        }
        
        $this->info("Python 3 found: " . trim($pythonCheck));
        
        // Prompt for confirmation
        if (!$this->confirm('Ready to start the upload. Continue?', true)) {
            $this->info('Upload cancelled.');
            return 0;
        }

        try {
            $this->info('Starting YouTube upload...');
            $videoId = $this->uploader->upload(
                $file,
                $title,
                $description,
                $tags,
                $privacy,
                $category
            );

            if ($videoId) {
                $this->info('Upload successful!');
                $this->info("Video ID: {$videoId}");
                if ($videoId !== 'UPLOAD_COMPLETED_BUT_ID_UNKNOWN') {
                    $this->info("Video URL: https://www.youtube.com/watch?v={$videoId}");
                } else {
                    $this->warn("Video was uploaded successfully but ID could not be automatically extracted.");
                    $this->warn("Please check your YouTube Studio: https://studio.youtube.com");
                }
                return 0;
            } else {
                $this->error('Upload failed: No video ID returned.');
                return 1;
            }
        } catch (Exception $e) {
            $this->error('Upload failed: ' . $e->getMessage());
            Log::error('YouTube upload test failed: ' . $e->getMessage());
            
            // Check for common errors and provide helpful advice
            $errorMsg = strtolower($e->getMessage());
            
            if (strpos($errorMsg, 'login') !== false || strpos($errorMsg, 'password') !== false) {
                $this->line("\nTips for authentication issues:");
                $this->line("1. Verify your email and password are correct in the .env file");
                $this->line("2. Enable 'Less secure app access' in your Google account settings");
                $this->line("3. Try using an App Password instead of your regular password");
                $this->line("4. Check if you need to solve a CAPTCHA by logging in manually once");
            } elseif (strpos($errorMsg, 'selenium') !== false || strpos($errorMsg, 'webdriver') !== false) {
                $this->line("\nTips for Selenium issues:");
                $this->line("1. Make sure Python and Selenium are installed:");
                $this->line("   pip3 install selenium webdriver-manager");
                $this->line("2. A compatible browser (Chrome or Firefox) must be installed");
                $this->line("3. For headless servers, use a virtual display:");
                $this->line("   sudo apt-get install xvfb");
                $this->line("   pip3 install pyvirtualdisplay");
            }
            
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
        
        // Try to find an existing video file in the storage
        $videoFiles = glob(storage_path('app/public/videos/*.mp4'));
        if (!empty($videoFiles)) {
            $sourceVideo = $videoFiles[0];
            $this->info("Using existing video as test: {$sourceVideo}");
            File::copy($sourceVideo, $testVideoPath);
            return $testVideoPath;
        }
        
        // Check if ffmpeg is available
        $ffmpegCheck = shell_exec('which ffmpeg 2>&1');
        if (empty($ffmpegCheck)) {
            $this->warn('ffmpeg not found. Unable to create a test video.');
            return null;
        }
        
        // Create a 5-second test video with ffmpeg
        $command = sprintf(
            'ffmpeg -f lavfi -i color=c=blue:s=320x240:d=5 -c:v libx264 -tune stillimage -pix_fmt yuv420p %s',
            $testVideoPath
        );
        
        $this->info("Running: {$command}");
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error('Failed to create test video with ffmpeg.');
            return null;
        }
        
        $this->info('Test video created successfully.');
        return $testVideoPath;
    }
} 