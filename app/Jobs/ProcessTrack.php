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
            $response = Http::timeout(60)->get($url);
            
            if (!$response->successful()) {
                throw new Exception("Failed to download file from {$url}. Status: {$response->status()}");
            }
            
            $extension = $this->getExtensionFromUrl($url);
            $filename = Str::random(40) . '.' . $extension;
            $path = "{$directory}/" . $filename;
            
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
            $mp3FullPath = Storage::disk('public')->path($mp3Path);
            $imageFullPath = Storage::disk('public')->path($imagePath);
            
            $outputFilename = Str::random(40) . '.mp4';
            $outputDirectory = 'videos';
            $outputPath = $outputDirectory . '/' . $outputFilename;
            $outputFullPath = Storage::disk('public')->path($outputPath);
            
            // Ensure videos directory exists
            Storage::disk('public')->makeDirectory($outputDirectory, 0755, true, true);
            
            // Alternative approach that creates a slideshow with audio
            // This doesn't use libx264 directly, but uses FFmpeg's default available video codec
            $command = "ffmpeg -y -loop 1 -i {$imageFullPath} -i {$mp3FullPath} -c:v mjpeg -q:v 2 -c:a copy -shortest {$outputFullPath}";
            
            Log::info("Running FFmpeg command: {$command}");
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("FFmpeg command failed with return code {$returnCode}: " . implode("\n", $output));
            }
            
            if (!file_exists($outputFullPath)) {
                throw new Exception("Output file was not created");
            }
            
            return $outputPath;
        } catch (Exception $e) {
            Log::error("Failed to create MP4: {$e->getMessage()}", [
                'track_id' => $this->track->id,
                'mp3_path' => $mp3Path,
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
                'output' => $output ?? []
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
        
        $genreNames = array_map('trim', explode(',', $genresString));
        $genreIds = [];
        
        foreach ($genreNames as $genreName) {
            if (empty($genreName)) {
                continue;
            }
            
            $genre = Genre::firstOrCreate(
                ['name' => $genreName],
                ['slug' => Str::slug($genreName)]
            );
            
            $genreIds[] = $genre->id;
        }
        
        // Sync genres with the track
        $this->track->genres()->sync($genreIds);
    }
}
