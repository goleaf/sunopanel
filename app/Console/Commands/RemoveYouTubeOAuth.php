<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RemoveYouTubeOAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:remove-oauth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unused YouTube OAuth authentication files and simplify uploader code';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up unused YouTube OAuth files...');
        
        // Files to remove
        $filesToRemove = [
            app_path('Services/YouTubeUploader.php'),
            app_path('Services/YouTubePlaylistManager.php'),
            app_path('Http/Controllers/Api/YouTubeAuthController.php'),
        ];
        
        // Directories to check/clean
        $dirsToCheck = [
            resource_path('views/youtube'),
        ];
        
        // Count of removed files
        $removed = 0;
        
        // Remove individual files
        foreach ($filesToRemove as $filePath) {
            if (File::exists($filePath)) {
                $this->line("Removing: {$filePath}");
                File::delete($filePath);
                $removed++;
            }
        }
        
        // Check directories for OAuth-related views
        foreach ($dirsToCheck as $dirPath) {
            if (File::isDirectory($dirPath)) {
                // Check for OAuth-specific files
                $oauthFiles = [
                    $dirPath . '/auth.blade.php',
                    $dirPath . '/callback.blade.php',
                ];
                
                foreach ($oauthFiles as $file) {
                    if (File::exists($file)) {
                        $this->line("Removing: {$file}");
                        File::delete($file);
                        $removed++;
                    }
                }
            }
        }
        
        // Update routes
        $routesFile = base_path('routes/web.php');
        if (File::exists($routesFile)) {
            $routesContent = File::get($routesFile);
            
            // Remove OAuth-specific routes
            $routesContent = preg_replace(
                '/Route::get\(\'\/youtube\/auth\'.*?\);.*?Route::get\(\'\/youtube\/callback\'.*?\);.*?Route::post\(\'\/youtube\/toggle-oauth\'.*?\);/s',
                '// OAuth routes removed',
                $routesContent
            );
            
            File::put($routesFile, $routesContent);
            $this->line("Updated routes file to remove OAuth routes");
        }
        
        // Remove Providers
        $providerFile = app_path('Providers/YouTubeServiceProvider.php');
        if (File::exists($providerFile)) {
            $this->line("Removing: {$providerFile}");
            File::delete($providerFile);
            $removed++;
        }
        
        // Clean up environment variables from .env file
        $envFile = base_path('.env');
        if (File::exists($envFile)) {
            $envContent = File::get($envFile);
            
            // List of OAuth environment variables to remove
            $varsToRemove = [
                'YOUTUBE_API_KEY',
                'YOUTUBE_CLIENT_ID',
                'YOUTUBE_CLIENT_SECRET',
                'YOUTUBE_REDIRECT_URI',
                'YOUTUBE_SCOPES',
                'YOUTUBE_ACCESS_TOKEN',
                'YOUTUBE_REFRESH_TOKEN',
                'YOUTUBE_TOKEN_EXPIRES_AT',
                'YOUTUBE_USE_OAUTH',
                'YOUTUBE_USE_SIMPLE_UPLOADER',
            ];
            
            foreach ($varsToRemove as $var) {
                $envContent = preg_replace("/^{$var}=.*$/m", "# {$var}= (removed)", $envContent);
            }
            
            File::put($envFile, $envContent);
            $this->line("Cleaned up OAuth environment variables from .env file");
        }
        
        // Summary
        $this->info("\nCleanup Summary:");
        $this->line("- Removed {$removed} files");
        $this->line("- Cleaned up OAuth routes");
        $this->line("- Updated environment configuration");
        
        $this->info("\nYouTube uploader has been simplified to use only username/password authentication.");
        $this->line("Make sure to set these variables in your .env file:");
        $this->line("YOUTUBE_EMAIL=your.email@gmail.com");
        $this->line("YOUTUBE_PASSWORD=your_password");
        
        $this->info("\nTo test the uploader, run:");
        $this->line("php artisan youtube:diagnostics");
        $this->line("php artisan youtube:test-upload --file=/path/to/video.mp4");
        
        return 0;
    }
}
