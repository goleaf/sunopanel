<?php

namespace App\Console\Commands;

use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpdateExistingTracksWithSunoId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracks:update-suno-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing tracks with Suno IDs by extracting them from URLs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating tracks with Suno IDs...');
        
        // Get all tracks without suno_id
        $tracks = Track::whereNull('suno_id')->get();
        $this->info("Found {$tracks->count()} tracks without Suno ID");
        
        $updatedCount = 0;
        
        foreach ($tracks as $track) {
            $sunoId = null;
            
            // Try to extract Suno ID from MP3 URL
            if (!empty($track->mp3_url)) {
                $sunoId = $this->extractSunoId($track->mp3_url);
            }
            
            // If not found, try to extract from image URL
            if (!$sunoId && !empty($track->image_url)) {
                $sunoId = $this->extractSunoId($track->image_url);
            }
            
            if ($sunoId) {
                $track->update(['suno_id' => $sunoId]);
                $this->comment("Updated track ID {$track->id}: {$track->title} with Suno ID: {$sunoId}");
                $updatedCount++;
            }
        }
        
        $this->info("Updated {$updatedCount} tracks with Suno IDs");
        
        return 0;
    }
    
    /**
     * Extract Suno ID from URL.
     *
     * @param string $url
     * @return string|null
     */
    protected function extractSunoId(string $url): ?string
    {
        // Check for Suno.ai unique ID in the URL
        $sunoIdPattern = '/https:\/\/cdn[0-9]?\.suno\.ai\/([a-f0-9-]{36})\.(?:mp3|jpeg|jpg|png|mp4)/i';
        if (preg_match($sunoIdPattern, $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
} 