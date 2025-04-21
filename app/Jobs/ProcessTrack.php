<?php

namespace App\Jobs;

use App\Models\Genre;
use App\Models\Track;
use Exception;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessTrack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * The track to process.
     *
     * @var Track
     */
    protected $track;

    /**
     * Create a new job instance.
     *
     * @param Track $track
     * @return void
     */
    public function __construct(Track $track)
    {
        $this->track = $track;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Update track status to processing
            $this->track->update([
                'status' => 'processing',
                'progress' => 0,
            ]);

            // Check if all files already exist - if so, we can just mark as completed
            if (!empty($this->track->mp3_path) && !empty($this->track->image_path) && !empty($this->track->mp4_path)) {
                // If the track has already been processed, just mark as completed and skip processing
                Log::info("Track {$this->track->id} - {$this->track->title} already has all required files, marking as completed");
                
                // Process genres if they haven't been processed
                if (!empty($this->track->genres_string) && $this->track->genres()->count() === 0) {
                    $this->processGenres($this->track->genres_string);
                }
                
                // Update track status to completed
                $this->track->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'error_message' => null,
                ]);
                
                return;
            }

            // Step 1: Download MP3 file (25%)
            $this->updateProgress(5, 'Downloading MP3 file...');
            $mp3Path = $this->downloadFile($this->track->mp3_url, 'mp3');
            $this->track->update([
                'mp3_path' => $mp3Path,
                'progress' => 25,
            ]);

            // Step 2: Download image file (50%)
            $this->updateProgress(30, 'Downloading image file...');
            $imagePath = $this->downloadFile($this->track->image_url, 'images');
            $this->track->update([
                'image_path' => $imagePath,
                'progress' => 50,
            ]);

            // Step 3: Create MP4 file (75%)
            $this->updateProgress(55, 'Creating MP4 file...');
            $mp4Path = $this->createMP4WithFFmpeg($mp3Path, $imagePath);
            $this->track->update([
                'mp4_path' => $mp4Path,
                'progress' => 75,
            ]);

            // Step 4: Process genres if available (100%)
            $this->updateProgress(80, 'Processing genres...');
            if (!empty($this->track->genres_string)) {
                $this->processGenres($this->track->genres_string);
            }

            // Update track status to completed
            $this->track->update([
                'status' => 'completed',
                'progress' => 100,
                'error_message' => null,
            ]);

            $this->updateProgress(100, 'Processing completed!');
        } catch (Exception $e) {
            Log::error('Track processing failed: ' . $e->getMessage(), [
                'track_id' => $this->track->id,
                'error' => $e->getMessage(),
            ]);

            // Update track with error
            $this->track->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update the track's progress.
     *
     * @param int $progress
     * @param string|null $message
     * @return void
     */
    protected function updateProgress(int $progress, ?string $message = null): void
    {
        $this->track->update([
            'progress' => $progress,
        ]);

        if ($message) {
            Log::info("Track {$this->track->id} - {$this->track->title}: {$message}");
        }
    }

    /**
     * Download a file from URL.
     *
     * @param string $url
     * @param string $directory
     * @return string The path to the downloaded file
     * @throws Exception If download fails
     */
    protected function downloadFile(string $url, string $directory): string
    {
        try {
            // Check for Suno.ai unique ID in the URL
            $sunoIdPattern = '/https:\/\/cdn[0-9]?\.suno\.ai\/([a-f0-9-]{36})\.(?:mp3|jpeg|jpg|png)/i';
            if (preg_match($sunoIdPattern, $url, $matches)) {
                $sunoId = $matches[1];
                
                // First check if this track has associated files already
                if ($directory === 'mp3' && !empty($this->track->mp3_path)) {
                    Log::info("Track already has MP3 file at {$this->track->mp3_path}, skipping download");
                    return $this->track->mp3_path;
                }
                
                if ($directory === 'images' && !empty($this->track->image_path)) {
                    Log::info("Track already has image file at {$this->track->image_path}, skipping download");
                    return $this->track->image_path;
                }
                
                // If both MP3 and image exist, and MP4 exists, the job can skip later stages
                if (!empty($this->track->mp3_path) && !empty($this->track->image_path) && !empty($this->track->mp4_path)) {
                    Log::info("Track already has all files processed (ID: {$sunoId}), potentially skipping processing");
                }
                
                // Then check for existing files with the Suno ID in the name
                $existingFiles = Storage::disk('public')->files($directory);
                foreach ($existingFiles as $existingFile) {
                    // If the file contains the Suno ID, use it instead of downloading again
                    if (Str::contains($existingFile, $sunoId)) {
                        Log::info("File with Suno ID {$sunoId} already exists at {$existingFile}, skipping download");
                        return $existingFile;
                    }
                }
                
                // If file doesn't exist yet, include Suno ID in the filename when saving
                $extension = $this->getExtensionFromUrl($url);
                $filename = "suno_{$sunoId}." . $extension;
                $path = "{$directory}/" . $filename;
            } else {
                // Check if we already have a file with this base name (without extension) - existing logic
                $baseFileName = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
                if (!empty($baseFileName)) {
                    $existingFiles = Storage::disk('public')->files($directory);
                    foreach ($existingFiles as $existingFile) {
                        $existingBaseName = pathinfo($existingFile, PATHINFO_FILENAME);
                        if (Str::contains($existingBaseName, $baseFileName)) {
                            Log::info("File with basename {$baseFileName} already exists at {$existingFile}, skipping download");
                            return $existingFile;
                        }
                    }
                }
                
                // If not a Suno URL or no match found, use the original random filename generation
                $extension = $this->getExtensionFromUrl($url);
                $filename = Str::random(40) . '.' . $extension;
                $path = "{$directory}/" . $filename;
            }
            
            // Download the file
            $response = Http::timeout(60)->get($url);
            
            if (!$response->successful()) {
                throw new Exception("Failed to download file from {$url}. Status: {$response->status()}");
            }
            
            // Ensure the directory exists
            Storage::disk('public')->makeDirectory($directory, 0755, true, true);
            
            Storage::disk('public')->put($path, $response->body());
            
            return $path;
        } catch (Exception $e) {
            Log::error("Failed to download file: {$e->getMessage()}", [
                'url' => $url,
                'track_id' => $this->track->id,
            ]);
            throw new Exception("Failed to download file: {$e->getMessage()}");
        }
    }

    /**
     * Get file extension from URL.
     *
     * @param string $url
     * @return string
     */
    protected function getExtensionFromUrl(string $url): string
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        
        if (empty($extension)) {
            // Default extensions based on the directory
            if (Str::contains($url, ['mp3', 'audio'])) {
                return 'mp3';
            } elseif (Str::contains($url, ['jpg', 'jpeg', 'png', 'image'])) {
                return 'jpg';
            }
            return 'dat'; // Default if no extension can be determined
        }
        
        return $extension;
    }

    /**
     * Create an MP4 file from audio and image using PHP-FFmpeg.
     *
     * @param string $mp3Path
     * @param string $imagePath
     * @return string The path to the created MP4 file
     * @throws Exception If creation fails
     */
    protected function createMP4WithFFmpeg(string $mp3Path, string $imagePath): string
    {
        try {
            // Get full paths for files
            $mp3FullPath = Storage::disk('public')->path($mp3Path);
            $imageFullPath = Storage::disk('public')->path($imagePath);
            
            // Check if files exist
            if (!file_exists($mp3FullPath)) {
                throw new Exception("MP3 file not found at {$mp3FullPath}");
            }
            
            if (!file_exists($imageFullPath)) {
                throw new Exception("Image file not found at {$imageFullPath}");
            }
            
            // Create unique output filename
            $outputFilename = Str::random(40) . '.mp4';
            $outputDirectory = 'videos';
            $outputPath = "{$outputDirectory}/{$outputFilename}";
            $outputFullPath = Storage::disk('public')->path($outputPath);
            
            // Ensure videos directory exists
            Storage::disk('public')->makeDirectory($outputDirectory, 0755, true, true);
            
            // Try multiple approaches for different systems
            $success = false;
            $errors = [];
            
            // Common scale filter to limit max dimension to 700px while maintaining aspect ratio
            $scaleFilter = "scale=w='min(700,iw)':h='min(700,ih)':force_original_aspect_ratio=decrease";
            
            // First attempt: Simple approach with mjpeg (most compatible)
            try {
                $escapedImagePath = escapeshellarg($imageFullPath);
                $escapedMp3Path = escapeshellarg($mp3FullPath);
                $escapedOutputPath = escapeshellarg($outputFullPath);
                
                $command = "ffmpeg -y -loop 1 -framerate 1 -i {$escapedImagePath} -i {$escapedMp3Path} -c:v mjpeg -q:v 3 -c:a copy -vf \"{$scaleFilter}\" -shortest {$escapedOutputPath} 2>&1";
                
                Log::info("Running FFmpeg command (Attempt 1): {$command}");
                
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && file_exists($outputFullPath)) {
                    Log::info("Successfully created MP4 with first method");
                    $success = true;
                } else {
                    $errors[] = "First method failed: " . implode("\n", $output);
                }
            } catch (Exception $e) {
                $errors[] = "First method exception: " . $e->getMessage();
            }
            
            // Second attempt: Try with libx264 if available
            if (!$success) {
                try {
                    // Combined scale filter that handles both the aspect ratio and the even dimensions requirement for libx264
                    $complexScaleFilter = "{$scaleFilter},scale=trunc(iw/2)*2:trunc(ih/2)*2";
                    
                    $command = "ffmpeg -y -loop 1 -framerate 1 -i {$escapedImagePath} -i {$escapedMp3Path} -c:v libx264 -tune stillimage -pix_fmt yuv420p -c:a aac -b:a 192k -shortest -vf \"{$complexScaleFilter}\" {$escapedOutputPath} 2>&1";
                    
                    Log::info("Running FFmpeg command (Attempt 2): {$command}");
                    
                    exec($command, $output, $returnCode);
                    
                    if ($returnCode === 0 && file_exists($outputFullPath)) {
                        Log::info("Successfully created MP4 with second method");
                        $success = true;
                    } else {
                        $errors[] = "Second method failed: " . implode("\n", $output);
                    }
                } catch (Exception $e) {
                    $errors[] = "Second method exception: " . $e->getMessage();
                }
            }
            
            // Fall back to direct copy with no re-encoding
            if (!$success) {
                try {
                    $tempDir = sys_get_temp_dir();
                    $tempImage = "{$tempDir}/temp_image_" . Str::random(10) . ".jpg";
                    copy($imageFullPath, $tempImage); // Make a temp copy to avoid permission issues
                    
                    $command = "ffmpeg -y -loop 1 -framerate 1 -t 3600 -i {$tempImage} -i {$escapedMp3Path} -c:v png -c:a copy -vf \"{$scaleFilter}\" -shortest {$escapedOutputPath} 2>&1";
                    
                    Log::info("Running FFmpeg command (Final attempt): {$command}");
                    
                    exec($command, $output, $returnCode);
                    
                    if (file_exists($tempImage)) {
                        unlink($tempImage); // Clean up temp file
                    }
                    
                    if ($returnCode === 0 && file_exists($outputFullPath)) {
                        Log::info("Successfully created MP4 with fallback method");
                        $success = true;
                    } else {
                        $errors[] = "Fallback method failed: " . implode("\n", $output);
                    }
                } catch (Exception $e) {
                    $errors[] = "Fallback method exception: " . $e->getMessage();
                }
            }
            
            // If all methods failed
            if (!$success) {
                $errorMsg = "All FFmpeg methods failed: " . implode("\n", $errors);
                Log::error($errorMsg);
                throw new Exception($errorMsg);
            }
            
            if (!file_exists($outputFullPath)) {
                throw new Exception("Output file was not created despite successful return code");
            }
            
            // Return the relative path for storage
            return $outputPath;
        } catch (Exception $e) {
            Log::error("Failed to create MP4: {$e->getMessage()}", [
                'track_id' => $this->track->id,
                'mp3_path' => $mp3Path,
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to create MP4: {$e->getMessage()}");
        }
    }

    /**
     * Process genres string and attach them to the track.
     *
     * @param string $genresString
     * @return void
     */
    protected function processGenres(string $genresString): void
    {
        if (empty($genresString)) {
            return;
        }
        
        Log::info("Processing genres for track {$this->track->id} - {$this->track->title}: '{$genresString}'");
        
        // Split by comma and remove duplicates
        $genreNames = array_unique(array_map('trim', explode(',', $genresString)));
        $genreIds = [];
        
        foreach ($genreNames as $genreName) {
            if (empty($genreName)) {
                continue;
            }
            
            try {
                // Normalize genre name for consistency
                $normalizedName = ucwords(strtolower($genreName));
                $slug = Str::slug($normalizedName);
                
                // First try to find existing genre by slug
                $genre = Genre::firstWhere('slug', $slug);
                
                // If not found, create a new one
                if (!$genre) {
                    $genre = Genre::create([
                        'name' => $normalizedName,
                        'slug' => $slug
                    ]);
                }
                
                Log::info("Genre found/created: {$normalizedName} with ID {$genre->id}");
                $genreIds[] = $genre->id;
            } catch (\Exception $e) {
                // Log the error but continue processing other genres
                Log::warning("Failed to process genre '{$genreName}': {$e->getMessage()}");
            }
        }
        
        if (!empty($genreIds)) {
            // Sync genres with the track
            Log::info("Syncing genres for track {$this->track->id}: " . implode(', ', $genreIds));
            $this->track->genres()->sync($genreIds);
            
            // Verify genres were attached
            $this->track->refresh();
            $attachedGenres = $this->track->genres->pluck('name')->toArray();
            Log::info("Attached genres for track {$this->track->id}: " . implode(', ', $attachedGenres));
        } else {
            Log::warning("No valid genres to attach for track {$this->track->id}");
        }
    }
}
