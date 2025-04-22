<?php

namespace App\Jobs;

use App\Models\Track;
use App\Services\SimpleYouTubeUploader;
use App\Services\YouTubeApiService;
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

    /**
     * The track instance.
     *
     * @var Track
     */
    protected $track;

    /**
     * The video title override.
     * 
     * @var string|null
     */
    protected $title;

    /**
     * The video description override.
     * 
     * @var string|null
     */
    protected $description;

    /**
     * Whether to add the video to a playlist.
     * 
     * @var bool
     */
    protected $addToPlaylist;

    /**
     * The privacy status override.
     * 
     * @var string|null
     */
    protected $privacyStatus;
    
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
    public $backoff = 300; // 5 minutes
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes
    
    /**
     * Create a new job instance.
     *
     * @param Track $track
     * @param string|null $title
     * @param string|null $description
     * @param bool $addToPlaylist
     * @param string|null $privacyStatus
     */
    public function __construct(
        Track $track,
        ?string $title = null,
        ?string $description = null,
        bool $addToPlaylist = false,
        ?string $privacyStatus = null
    ) {
        $this->track = $track;
        $this->title = $title;
        $this->description = $description;
        $this->addToPlaylist = $addToPlaylist;
        $this->privacyStatus = $privacyStatus ?? config('youtube.default_privacy_status', 'unlisted');
    }

    /**
     * Execute the job.
     *
     * @param SimpleYouTubeUploader $uploader
     * @return void
     */
    public function handle(SimpleYouTubeUploader $uploader)
    {
        Log::info("Starting YouTube upload for track #{$this->track->id}", [
            'track_id' => $this->track->id,
            'title' => $this->track->title,
        ]);
        
        try {
            // Check if the track already has a YouTube video ID
        if ($this->track->youtube_video_id) {
                Log::warning("Track #{$this->track->id} already has a YouTube video ID", [
                    'track_id' => $this->track->id,
                    'youtube_id' => $this->track->youtube_video_id,
                ]);
            return;
        }
        
            // Find the video file
        $videoPath = storage_path('app/public/videos/' . $this->track->mp4_file);
            if (!file_exists($videoPath) || !is_readable($videoPath)) {
                throw new \Exception("Video file not found or not readable: {$videoPath}");
        }
        
            // Prepare title
            $title = $this->title ?? $this->track->title;
        
            // Prepare description
            $description = $this->description ?? "Generated with SunoPanel\nTrack: {$this->track->title}";
        if (!empty($this->track->genres_string)) {
                $description .= "\nGenres: {$this->track->genres_string}";
        }
        
            // Prepare tags from genres
            $genres = $this->track->genres()->pluck('name')->toArray();
            $tags = array_merge($genres, ['sunopanel', 'ai music', 'ai generated']);

            // Get configured privacy status or use default
            $privacyStatus = $this->privacyStatus ?? config('youtube.default_privacy_status', 'unlisted');
        
        // Upload the video
        $videoId = $uploader->upload(
            $videoPath,
            $title,
            $description,
            $tags,
                $privacyStatus,
                'Music'
        );
        
        if (!$videoId) {
                throw new \Exception("Failed to upload video to YouTube");
        }
        
            // Update the track with the YouTube ID
        $this->track->youtube_video_id = $videoId;
        $this->track->youtube_uploaded_at = now();
            $this->track->save();

            Log::info("Track #{$this->track->id} successfully uploaded to YouTube", [
                'track_id' => $this->track->id,
                'youtube_id' => $videoId,
            ]);

            // Add to playlist if requested and the track has genres
            if ($this->addToPlaylist && $this->track->genres->isNotEmpty()) {
                $this->addToGenrePlaylists($uploader, $videoId);
                }

            } catch (\Exception $e) {
            Log::error("Failed to upload track #{$this->track->id} to YouTube: {$e->getMessage()}", [
                'track_id' => $this->track->id,
                'exception' => $e,
            ]);
            
            throw $e;
            }
        }
        
    /**
     * Add the video to genre-based playlists.
     *
     * @param SimpleYouTubeUploader $uploader
     * @param string $videoId
     * @return void
     */
    protected function addToGenrePlaylists(SimpleYouTubeUploader $uploader, string $videoId): void
    {
        // Only proceed if OAuth is enabled
        if (!config('youtube.use_oauth', false)) {
            Log::info("Skipping playlist addition - OAuth is not enabled");
            return;
        }

        foreach ($this->track->genres as $genre) {
            $playlistName = "SunoPanel - {$genre->name}";
            
            try {
                // Find or create playlist
                $playlistId = $uploader->findOrCreatePlaylist(
                    $playlistName,
                    "SunoPanel AI generated music - {$genre->name} genre",
                    'public'
            );
            
                if (!$playlistId) {
                    Log::warning("Could not find or create playlist for genre: {$genre->name}");
                    continue;
                }
                
                // Add video to playlist
                $success = $uploader->addToPlaylist($videoId, $playlistId);
                
                if ($success) {
                    Log::info("Added track #{$this->track->id} to {$genre->name} playlist", [
                        'track_id' => $this->track->id,
                        'youtube_id' => $videoId,
                        'playlist_id' => $playlistId,
                    ]);
                    
                    // Update track with playlist ID (store just the last one, or we could store multiple in JSON)
                    $this->track->youtube_playlist_id = $playlistId;
                    $this->track->save();
            } else {
                    Log::warning("Failed to add track #{$this->track->id} to {$genre->name} playlist", [
                        'track_id' => $this->track->id,
                        'youtube_id' => $videoId,
                        'playlist_id' => $playlistId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Error adding track #{$this->track->id} to {$genre->name} playlist: {$e->getMessage()}", [
                    'track_id' => $this->track->id,
                    'youtube_id' => $videoId,
                    'genre' => $genre->name,
                    'exception' => $e,
                ]);
                // Continue with other genres - don't throw exception
            }
        }
    }
} 