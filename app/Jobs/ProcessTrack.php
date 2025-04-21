<?php

namespace App\Jobs;

use App\Models\Track;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

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
     * The track instance.
     *
     * @var \App\Models\Track
     */
    protected $track;

    /**
     * Create a new job instance.
     */
    public function __construct(Track $track)
    {
        $this->track = $track;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateProgress(10, 'processing');
            
            // Download MP3
            $mp3Path = $this->downloadFile($this->track->mp3_url, 'mp3');
            $this->track->mp3_path = $mp3Path;
            $this->track->save();
            $this->updateProgress(40);
            
            // Download Image
            $imagePath = $this->downloadFile($this->track->image_url, 'jpg');
            $this->track->image_path = $imagePath;
            $this->track->save();
            $this->updateProgress(70);
            
            // Create MP4
            $mp4Path = $this->createMP4($mp3Path, $imagePath);
            $this->track->mp4_path = $mp4Path;
            $this->track->save();
            
            $this->updateProgress(100, 'completed');
        } catch (\Exception $e) {
            Log::error('Track processing error: ' . $e->getMessage(), [
                'track_id' => $this->track->id,
                'exception' => $e
            ]);
            
            $this->updateProgress(0, 'failed', $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Download a file from URL.
     *
     * @param string $url
     * @param string $extension
     * @return string
     */
    protected function downloadFile(string $url, string $extension): string
    {
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
        $path = 'tracks/' . uniqid() . '_' . $filename;
        
        $response = Http::timeout(120)->get($url);
        
        if ($response->successful()) {
            Storage::disk('public')->put($path, $response->body());
            return $path;
        }
        
        throw new \Exception("Failed to download file from {$url}. Status: {$response->status()}");
    }

    /**
     * Create MP4 from MP3 and image using direct FFmpeg command.
     *
     * @param string $mp3Path
     * @param string $imagePath
     * @return string
     */
    protected function createMP4(string $mp3Path, string $imagePath): string
    {
        // Define paths for input and output files
        $outputFilename = 'tracks/' . uniqid() . '_' . pathinfo($mp3Path, PATHINFO_FILENAME) . '.mp4';
        
        $mp3FullPath = Storage::disk('public')->path($mp3Path);
        $imageFullPath = Storage::disk('public')->path($imagePath);
        $outputFullPath = Storage::disk('public')->path($outputFilename);
        
        // Ensure output directory exists
        $outputDir = dirname($outputFullPath);
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Build FFmpeg command
        $ffmpegCommand = [
            'ffmpeg',
            '-loop', '1',                   // Loop the image
            '-i', $imageFullPath,           // Input image file
            '-i', $mp3FullPath,             // Input audio file
            '-c:v', 'libx264',              // Video codec
            '-tune', 'stillimage',          // Optimize for still image
            '-c:a', 'aac',                  // Audio codec
            '-b:a', '192k',                 // Audio bitrate
            '-pix_fmt', 'yuv420p',          // Pixel format
            '-shortest',                    // Duration based on audio length
            '-vf', 'scale=trunc(iw/2)*2:trunc(ih/2)*2', // Ensure even dimensions
            '-y',                           // Overwrite output file if exists
            $outputFullPath                 // Output file
        ];

        // Run FFmpeg command as a process
        $process = new Process($ffmpegCommand);
        $process->setTimeout(300); // 5 minutes timeout
        
        $process->run();
        
        // Check if the process was successful
        if (!$process->isSuccessful()) {
            throw new \Exception('FFmpeg error: ' . $process->getErrorOutput());
        }
        
        return $outputFilename;
    }

    /**
     * Update the track processing progress.
     *
     * @param int $progress
     * @param string|null $status
     * @param string|null $errorMessage
     * @return void
     */
    protected function updateProgress(int $progress, ?string $status = null, ?string $errorMessage = null): void
    {
        $this->track->progress = $progress;
        
        if ($status) {
            $this->track->status = $status;
        }
        
        if ($errorMessage) {
            $this->track->error_message = $errorMessage;
        }
        
        $this->track->save();
    }
}
