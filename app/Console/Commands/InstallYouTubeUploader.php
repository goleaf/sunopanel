<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class InstallYouTubeUploader extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install YouTube uploader scripts and dependencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Installing YouTube Uploader...');
        
        // Step 1: Check Python and pip
        $this->info('Checking Python and pip...');
        $pythonExists = $this->checkCommand('python3 --version');
        $pipExists = $this->checkCommand('pip3 --version');
        
        if (!$pythonExists) {
            $this->error('Python 3 is not installed. Please install Python 3 first.');
            return 1;
        }
        
        if (!$pipExists) {
            $this->error('pip3 is not installed. Please install pip3 first.');
            return 1;
        }
        
        // Step 2: Install Python dependencies
        $this->info('Installing Python dependencies...');
        $result = $this->executeCommand('pip3 install selenium webdriver-manager pyvirtualdisplay');
        
        if (!$result) {
            $this->error('Failed to install Python dependencies.');
            return 1;
        }
        
        // Step 3: Create/update uploader scripts
        $this->info('Installing uploader scripts...');
        
        // Create the uploader script
        $uploaderScript = file_get_contents('/usr/local/bin/youtube-direct-upload');
        if (empty($uploaderScript)) {
            $this->error('Source uploader script not found. Please check the installation.');
            return 1;
        }
        
        // Create scripts directory if it doesn't exist
        $scriptsDir = storage_path('app/scripts');
        if (!is_dir($scriptsDir)) {
            File::makeDirectory($scriptsDir, 0755, true);
        }
        
        // Write the script to storage directory
        $scriptPath = $scriptsDir . '/youtube-direct-upload';
        File::put($scriptPath, $uploaderScript);
        chmod($scriptPath, 0755);
        
        // Create symlink
        $symlinkPath = base_path('vendor/bin/youtube-direct-upload');
        if (!file_exists($symlinkPath)) {
            if (is_dir(dirname($symlinkPath))) {
                symlink($scriptPath, $symlinkPath);
            }
        }
        
        // Step 4: Set up configuration
        $this->info('Setting up configuration...');
        $this->setupEnvironmentVariables();
        
        // Step 5: Add test video
        $this->createTestVideo();
        
        // Final step: Run diagnostics
        $this->info('Installation complete. Running diagnostics...');
        $this->call('youtube:diagnostics');
        
        $this->info('');
        $this->info('YouTube uploader installation is complete!');
        $this->info('To test the uploader, run:');
        $this->line('php artisan youtube:test-upload');
        
        return 0;
    }
    
    /**
     * Check if a command exists and is executable
     */
    protected function checkCommand(string $command): bool
    {
        try {
            $process = Process::fromShellCommandline($command);
            $process->run();
            
            if ($process->isSuccessful()) {
                $this->line('✅ ' . trim($process->getOutput()));
                return true;
            }
        } catch (\Exception $e) {
            // Catch any exceptions
        }
        
        return false;
    }
    
    /**
     * Run a shell command
     */
    protected function executeCommand(string $command): bool
    {
        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300); // 5 minutes
            
            $this->line('Running: ' . $command);
            
            $process->run(function ($type, $buffer) {
                $this->line($buffer);
            });
            
            return $process->isSuccessful();
        } catch (\Exception $e) {
            $this->error('Failed to run command: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set up environment variables
     */
    protected function setupEnvironmentVariables(): void
    {
        $envPath = base_path('.env');
        
        if (File::exists($envPath)) {
            $env = File::get($envPath);
            
            // Add default YouTube environment variables if they don't exist
            if (strpos($env, 'YOUTUBE_EMAIL') === false) {
                $env .= "\n# YouTube Upload Settings\n";
                $env .= "YOUTUBE_EMAIL=\n";
                $env .= "YOUTUBE_PASSWORD=\n";
                $env .= "YOUTUBE_DEFAULT_PRIVACY_STATUS=unlisted\n";
                $env .= "YOUTUBE_DEFAULT_CATEGORY_ID=10\n";
                $env .= "YOUTUBE_BROWSER=chrome\n";
                $env .= "YOUTUBE_HEADLESS=true\n";
                
                File::put($envPath, $env);
                $this->line('✅ Added YouTube environment variables to .env file');
            } else {
                $this->line('✅ YouTube environment variables already exist in .env file');
            }
        } else {
            $this->warn('⚠️ .env file not found. Cannot configure environment variables.');
        }
    }
    
    /**
     * Create a test video file
     */
    protected function createTestVideo(): void
    {
        $testDir = storage_path('app/public/test');
        if (!is_dir($testDir)) {
            File::makeDirectory($testDir, 0755, true);
        }
        
        $testVideoPath = $testDir . '/test-video.mp4';
        
        // Skip if test video already exists
        if (File::exists($testVideoPath) && filesize($testVideoPath) > 0) {
            $this->line('✅ Test video already exists');
            return;
        }
        
        // Try to find an existing video to copy
        $videoFiles = glob(storage_path('app/public/videos/*.mp4'));
        if (!empty($videoFiles)) {
            $sourceVideo = $videoFiles[0];
            File::copy($sourceVideo, $testVideoPath);
            $this->line('✅ Created test video from existing file: ' . basename($sourceVideo));
            return;
        }
        
        // Try to create with ffmpeg
        if ($this->checkCommand('which ffmpeg')) {
            $command = "ffmpeg -f lavfi -i color=c=blue:s=320x240:d=5 -c:v libx264 -tune stillimage -pix_fmt yuv420p {$testVideoPath}";
            if ($this->executeCommand($command)) {
                $this->line('✅ Created test video using ffmpeg');
                return;
            }
        }
        
        $this->warn('⚠️ Could not create test video');
    }
} 