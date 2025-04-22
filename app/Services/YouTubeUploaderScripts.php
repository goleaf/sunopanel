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
            self::installClientSecrets();
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
        $scriptPath = storage_path('app/scripts/youtube-direct-upload');
        File::ensureDirectoryExists(dirname($scriptPath));
        
        // Copy the script from the existing file if it exists, otherwise use default content
        if (file_exists(__DIR__ . '/../../storage/app/scripts/youtube-direct-upload')) {
            $scriptContent = file_get_contents(__DIR__ . '/../../storage/app/scripts/youtube-direct-upload');
        } else {
            // Default script content
            $scriptContent = "#!/usr/bin/env python3\n";
            $scriptContent .= "# YouTube Direct Upload Script\n";
            $scriptContent .= "# Please install the script manually\n";
        }
        
        File::put($scriptPath, $scriptContent);
        chmod($scriptPath, 0755);
        
        // Create symlink in vendor/bin for backward compatibility
        $vendorBinPath = base_path('vendor/bin/youtube-direct-upload');
        if (!file_exists($vendorBinPath) && is_dir(dirname($vendorBinPath))) {
            symlink($scriptPath, $vendorBinPath);
        }
    }
    
    /**
     * Install the client secrets script
     */
    private static function installClientSecrets(): void
    {
        $scriptPath = storage_path('app/scripts/youtube-client-secrets');
        File::ensureDirectoryExists(dirname($scriptPath));
        
        // Copy the script from the existing file if it exists, otherwise use default content
        if (file_exists(__DIR__ . '/../../storage/app/scripts/youtube-client-secrets')) {
            $scriptContent = file_get_contents(__DIR__ . '/../../storage/app/scripts/youtube-client-secrets');
        } else {
            // Default script content
            $scriptContent = "#!/usr/bin/env php\n";
            $scriptContent .= "<?php\n";
            $scriptContent .= "# YouTube Client Secrets Script\n";
            $scriptContent .= "# Please install the script manually\n";
        }
        
        File::put($scriptPath, $scriptContent);
        chmod($scriptPath, 0755);
        
        // Create symlink in vendor/bin for backward compatibility
        $vendorBinPath = base_path('vendor/bin/youtube-client-secrets');
        if (!file_exists($vendorBinPath) && is_dir(dirname($vendorBinPath))) {
            symlink($scriptPath, $vendorBinPath);
        }
    }
} 