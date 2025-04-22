<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class YouTubeDiagnostics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:diagnostics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run diagnostics on the YouTube uploader configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running YouTube Uploader Diagnostics');
        $this->line('===============================');
        
        // Check credentials
        $this->info("\nCredentials Check:");
        $email = Config::get('youtube.email');
        $password = Config::get('youtube.password');
        
        if (empty($email)) {
            $this->error('❌ YouTube email not set. Set YOUTUBE_EMAIL in your .env file.');
        } else {
            $this->info('✅ YouTube email configured: ' . $email);
        }
        
        if (empty($password)) {
            $this->error('❌ YouTube password not set. Set YOUTUBE_PASSWORD in your .env file.');
        } else {
            $this->info('✅ YouTube password configured: ' . str_repeat('*', strlen($password)));
        }
        
        // Check uploader script
        $this->info("\nUploader Script Check:");
        $scriptPath = Config::get('youtube.upload_script', '/usr/local/bin/youtube-direct-upload');
        
        if (file_exists($scriptPath)) {
            $this->info("✅ Upload script found at: {$scriptPath}");
        } else {
            // Try alternative locations
            $altLocations = [
                storage_path('app/scripts/youtube-direct-upload'),
                base_path('vendor/bin/youtube-direct-upload')
            ];
            
            $found = false;
            foreach ($altLocations as $path) {
                if (file_exists($path)) {
                    $this->info("✅ Upload script found at alternative location: {$path}");
                    $this->warn("   Consider updating YOUTUBE_UPLOAD_SCRIPT in .env to point to this location.");
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $this->error("❌ Upload script not found at any expected location:");
                $this->line("   - {$scriptPath}");
                foreach ($altLocations as $path) {
                    $this->line("   - {$path}");
                }
            }
        }
        
        // Check dependencies
        $this->info("\nDependencies Check:");
        
        // Python
        $pythonVersion = $this->getCommandOutput('python3 --version');
        if ($pythonVersion) {
            $this->info("✅ {$pythonVersion}");
        } else {
            $this->error("❌ Python 3 not found. Please install Python 3.");
        }
        
        // PIP
        $pipVersion = $this->getCommandOutput('pip3 --version');
        if ($pipVersion) {
            $this->info("✅ {$pipVersion}");
        } else {
            $this->error("❌ pip3 not found. Please install pip3.");
        }
        
        // Check for Selenium
        $seleniumCheck = $this->getCommandOutput('pip3 show selenium');
        if (strpos($seleniumCheck, 'Name: selenium') !== false) {
            preg_match('/Version: ([0-9\.]+)/', $seleniumCheck, $matches);
            $version = $matches[1] ?? 'unknown';
            $this->info("✅ Selenium installed (version {$version})");
        } else {
            $this->error("❌ Selenium not installed. Install with: pip3 install selenium webdriver-manager");
        }
        
        // WebDriver Manager
        $webdriverCheck = $this->getCommandOutput('pip3 show webdriver-manager');
        if (strpos($webdriverCheck, 'Name: webdriver-manager') !== false) {
            preg_match('/Version: ([0-9\.]+)/', $webdriverCheck, $matches);
            $version = $matches[1] ?? 'unknown';
            $this->info("✅ WebDriver Manager installed (version {$version})");
        } else {
            $this->error("❌ WebDriver Manager not installed. Install with: pip3 install webdriver-manager");
        }
        
        // Check for Chrome/Firefox
        $browser = Config::get('youtube.browser', 'chrome');
        if ($browser === 'chrome') {
            $chromeCheck = $this->getCommandOutput('which google-chrome');
            if ($chromeCheck) {
                $chromeVersion = $this->getCommandOutput('google-chrome --version');
                $this->info("✅ Google Chrome found: {$chromeVersion}");
            } else {
                $this->error("❌ Google Chrome not found. Install Chrome or set YOUTUBE_BROWSER=firefox in .env");
            }
        } else {
            $firefoxCheck = $this->getCommandOutput('which firefox');
            if ($firefoxCheck) {
                $firefoxVersion = $this->getCommandOutput('firefox --version');
                $this->info("✅ Firefox found: {$firefoxVersion}");
            } else {
                $this->error("❌ Firefox not found. Install Firefox or set YOUTUBE_BROWSER=chrome in .env");
            }
        }
        
        // Check for virtual display on headless systems
        if (Config::get('youtube.headless', true)) {
            $this->info("\nHeadless Mode Check:");
            $xvfbCheck = $this->getCommandOutput('which Xvfb');
            if ($xvfbCheck) {
                $this->info("✅ Xvfb found for headless browser support");
            } else {
                $this->warn("⚠️ Xvfb not found. On headless servers, install Xvfb: sudo apt-get install xvfb");
            }
            
            $virtDisplayCheck = $this->getCommandOutput('pip3 show pyvirtualdisplay');
            if (strpos($virtDisplayCheck, 'Name: PyVirtualDisplay') !== false) {
                $this->info("✅ PyVirtualDisplay installed for headless browser support");
            } else {
                $this->warn("⚠️ PyVirtualDisplay not installed. On headless servers, install: pip3 install pyvirtualdisplay");
            }
        }
        
        // Check storage permissions
        $this->info("\nStorage Permissions Check:");
        $videosPath = storage_path('app/public/videos');
        if (is_dir($videosPath)) {
            if (is_writable($videosPath)) {
                $this->info("✅ Videos directory is writable: {$videosPath}");
            } else {
                $this->error("❌ Videos directory is not writable: {$videosPath}");
            }
        } else {
            $this->warn("⚠️ Videos directory does not exist: {$videosPath}");
        }
        
        // Check temp directory access
        $tempWritable = $this->isTempWritable();
        if ($tempWritable) {
            $this->info("✅ Temporary directory is writable");
        } else {
            $this->error("❌ Temporary directory is not writable. This is needed for browser screenshots.");
        }
        
        // Summary and recommendations
        $this->info("\n📋 Summary:");
        if (empty($email) || empty($password)) {
            $this->line("1. Set YouTube credentials in .env");
        }
        
        if (!file_exists($scriptPath)) {
            $this->line("2. Install the YouTube uploader script");
        }
        
        if (!$pythonVersion || !$seleniumCheck || !$webdriverCheck) {
            $this->line("3. Install Python dependencies:");
            $this->line("   pip3 install selenium webdriver-manager pyvirtualdisplay");
        }
        
        $this->info("\n📚 Documentation:");
        $this->line("To test a YouTube upload, run:");
        $this->line("php artisan youtube:test-upload --file=/path/to/video.mp4");
        
        return 0;
    }
    
    /**
     * Get the output of a command
     *
     * @param string $command
     * @return string|null
     */
    private function getCommandOutput(string $command): ?string
    {
        try {
            $output = shell_exec($command . ' 2>/dev/null');
            return $output ? trim($output) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if temp directory is writable
     *
     * @return bool
     */
    private function isTempWritable(): bool
    {
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'youtube_test');
            if ($tempFile) {
                unlink($tempFile);
                return true;
            }
        } catch (\Exception $e) {
            // Failed to create temp file
        }
        
        return false;
    }
} 