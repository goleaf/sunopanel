<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class YouTubeUploaderScripts
{
    /**
     * Install the YouTube uploader scripts
     *
     * @return bool True if installed successfully
     */
    public static function install(): bool
    {
        try {
            self::installDirectUploader();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to install YouTube uploader scripts: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Install the direct uploader script
     */
    private static function installDirectUploader(): void
    {
        // Destination in the storage/app/scripts directory
        $scriptPath = storage_path('app/scripts/youtube-direct-upload');
        File::ensureDirectoryExists(dirname($scriptPath));
        
        // Source path (assuming it's in /usr/local/bin)
        $sourcePath = '/usr/local/bin/youtube-direct-upload';
        
        if (file_exists($sourcePath)) {
            // Copy the script from the system-wide location
            File::copy($sourcePath, $scriptPath);
            Log::info("Copied YouTube direct upload script from {$sourcePath}");
        } else {
            // Create a placeholder script with instructions
            $scriptContent = "#!/usr/bin/env python3\n\n";
            $scriptContent .= "# YouTube Direct Upload Script\n";
            $scriptContent .= "# This is a placeholder. Please install the full script at:\n";
            $scriptContent .= "# /usr/local/bin/youtube-direct-upload\n\n";
            $scriptContent .= "import sys\n";
            $scriptContent .= "print('YouTube uploader script not properly installed.')\n";
            $scriptContent .= "print('Please run: php artisan youtube:install')\n";
            $scriptContent .= "sys.exit(1)\n";
            
            File::put($scriptPath, $scriptContent);
            Log::warning("Created placeholder YouTube direct upload script. Full script needs to be installed.");
        }
        
        // Make it executable
        chmod($scriptPath, 0755);
        
        // Create symlink in vendor/bin for backward compatibility
        $vendorBinPath = base_path('vendor/bin/youtube-direct-upload');
        if (!file_exists($vendorBinPath) && is_dir(dirname($vendorBinPath))) {
            if (file_exists($scriptPath)) {
                symlink($scriptPath, $vendorBinPath);
                Log::info("Created symlink at {$vendorBinPath}");
            }
        }
    }
} 