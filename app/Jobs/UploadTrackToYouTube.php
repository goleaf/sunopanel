<?php

namespace App\Jobs;

use App\Models\Track;
use App\Services\SimpleYouTubeUploader;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadTrackToYouTube implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Track $track;
    protected string $title;
    protected string $description;
    protected bool $addToPlaylist;
    protected string $privacyStatus;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;
    
    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;
    
    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 2;
    
    /**
     * Create a new job instance.
     */
    public function __construct(
        Track $track,
        string $title,
        string $description = '',
        bool $addToPlaylist = true,
        string $privacyStatus = 'unlisted'
    ) {
        $this->track = $track;
        $this->title = $title;
        $this->description = $description;
        $this->addToPlaylist = $addToPlaylist;
        $this->privacyStatus = $privacyStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting YouTube upload job for track ID {$this->track->id}");
        
        // Check if already uploaded
        if ($this->track->youtube_video_id) {
            Log::warning("Track ID {$this->track->id} already has a YouTube video ID: {$this->track->youtube_video_id}");
            return;
        }
        
        // Check if track is completed
        if ($this->track->status !== 'completed') {
            Log::warning("Track ID {$this->track->id} is not completed, status: {$this->track->status}");
            return;
        }
        
        // Check if video file exists
        $videoPath = storage_path('app/public/videos/' . $this->track->mp4_file);
        if (!file_exists($videoPath)) {
            Log::error("Video file for track ID {$this->track->id} not found at {$videoPath}");
            return;
        }
        
        // Prepare video metadata
        $title = $this->title ?: $this->track->title;
        $description = $this->description ?: "Generated with SunoPanel";
        
        // Get video tags from track genres
        $tags = [];
        if (!empty($this->track->genres_string)) {
            $tags = explode(',', $this->track->genres_string);
            $tags = array_map('trim', $tags);
        }
        
        // Get the playlist title (genre) if needed
        $playlistTitle = null;
        if ($this->addToPlaylist && !empty($this->track->genres_string)) {
            $genres = explode(',', $this->track->genres_string);
            $playlistTitle = trim($genres[0]); // Use the first genre as playlist name
        }
        
        try {
            // Determine which uploader to use
            if (config('youtube.use_simple_uploader')) {
                $this->uploadWithSimpleUploader($videoPath, $title, $description, $tags, $playlistTitle);
            } else {
                $this->uploadWithYouTubeAPI($videoPath, $title, $description, $tags, $playlistTitle);
            }
        } catch (\Exception $e) {
            Log::error("Exception during YouTube upload for track ID {$this->track->id}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Upload using the simple YouTube uploader (username/password)
     */
    private function uploadWithSimpleUploader(
        string $videoPath, 
        string $title, 
        string $description, 
        array $tags, 
        ?string $playlistTitle
    ): void {
        $uploader = new SimpleYouTubeUploader();
        
        // Upload the video
        $videoId = $uploader->upload(
            $videoPath,
            $title,
            $description,
            $tags,
            $this->privacyStatus
        );
        
        if (!$videoId) {
            Log::error("Failed to upload track ID {$this->track->id} to YouTube");
            throw new \Exception("YouTube upload failed: No video ID returned");
        }
        
        // Update track with YouTube information
        $this->track->youtube_video_id = $videoId;
        $this->track->youtube_uploaded_at = now();
        
        // Add to playlist if needed
        if ($playlistTitle) {
            try {
                $playlistId = $uploader->addToPlaylist($videoId, $playlistTitle);
                if ($playlistId) {
                    $this->track->youtube_playlist_id = $playlistId;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to add video to playlist, but upload was successful: " . $e->getMessage());
                // Don't re-throw this exception as the upload succeeded
            }
        }
        
        $this->track->save();
        Log::info("Successfully uploaded track ID {$this->track->id} to YouTube with video ID {$videoId}");
    }
    
    /**
     * Upload using the YouTube API (OAuth)
     */
    private function uploadWithYouTubeAPI(
        string $videoPath, 
        string $title, 
        string $description, 
        array $tags, 
        ?string $playlistTitle
    ): void {
        $youtubeService = app(YouTubeService::class);
        
        if ($playlistTitle) {
            // Upload to YouTube and add to playlist
            $result = $youtubeService->uploadVideoToPlaylist(
                $videoPath,
                $title,
                $description,
                $playlistTitle,
                $tags,
                $this->privacyStatus
            );
            
            if ($result && isset($result['video_id'])) {
                // Update track with YouTube information
                $this->track->youtube_video_id = $result['video_id'];
                $this->track->youtube_playlist_id = $result['playlist_id'] ?? null;
                $this->track->youtube_uploaded_at = now();
                $this->track->save();
                
                Log::info("Successfully uploaded track ID {$this->track->id} to YouTube with video ID {$result['video_id']}");
            } else {
                Log::error("Failed to upload track ID {$this->track->id} to YouTube");
                throw new \Exception("YouTube upload failed: No video ID returned");
            }
        } else {
            // Upload to YouTube without adding to playlist
            $videoId = $youtubeService->uploadVideo(
                $videoPath,
                $title,
                $description,
                $tags,
                $this->privacyStatus
            );
            
            if ($videoId) {
                // Update track with YouTube information
                $this->track->youtube_video_id = $videoId;
                $this->track->youtube_uploaded_at = now();
                $this->track->save();
                
                Log::info("Successfully uploaded track ID {$this->track->id} to YouTube with video ID {$videoId}");
            } else {
                Log::error("Failed to upload track ID {$this->track->id} to YouTube");
                throw new \Exception("YouTube upload failed: No video ID returned");
            }
        }
    }
} 