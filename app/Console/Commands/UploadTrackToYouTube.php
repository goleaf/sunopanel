<?php

namespace App\Console\Commands;

use App\Models\Track;
use App\Services\YouTubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class UploadTrackToYouTube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload 
                            {track_id? : ID of track to upload} 
                            {--file= : Path to MP4 file} 
                            {--title= : Video title} 
                            {--description= : Video description} 
                            {--tags= : Comma-separated tags} 
                            {--privacy=unlisted : Privacy setting (public, unlisted, private)}
                            {--privacy_status=unlisted : Alternative privacy setting name}
                            {--playlist= : Playlist name to add the video to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload an MP4 file or track to YouTube';

    /**
     * @var YouTubeService
     */
    protected $youtubeService;

    /**
     * Create a new command instance.
     *
     * @param YouTubeService $youtubeService
     */
    public function __construct(YouTubeService $youtubeService)
    {
        parent::__construct();
        $this->youtubeService = $youtubeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->youtubeService->isAuthenticated()) {
            $this->error("YouTube authentication required. Please authenticate first by visiting the YouTube settings page.");
            return 1;
        }
        
        // Check if we're uploading a track or a file
        $trackId = $this->argument('track_id');
        $filePath = $this->option('file');
        
        if ($trackId) {
            return $this->uploadTrack($trackId);
        } elseif ($filePath) {
            return $this->uploadFile($filePath);
        } else {
            $this->error("Either a track ID or file path must be provided.");
            return 1;
        }
    }
    
    /**
     * Get the privacy setting from options
     *
     * @return string
     */
    protected function getPrivacySetting()
    {
        // Check for either privacy or privacy_status option
        $privacy = $this->option('privacy');
        $privacyStatus = $this->option('privacy_status');
        
        // Return whichever one is set, with privacy taking precedence
        return $privacy !== 'unlisted' ? $privacy : $privacyStatus;
    }
    
    /**
     * Upload a track from the database
     *
     * @param int $trackId
     * @return int
     */
    protected function uploadTrack($trackId)
    {
        try {
            $track = Track::findOrFail($trackId);
            
            // Get the video path - directly use the mp4_path field for better reliability
            $videoPath = storage_path('app/public/' . $track->mp4_path);
            
            // Log the path being used
            $this->info("Looking for video file at: {$videoPath}");
            
            if (!File::exists($videoPath)) {
                $this->error("Video file not found for track #{$trackId}: {$videoPath}");
                return 1;
            }
            
            // Log file existence
            $this->info("Video file found, size: " . File::size($videoPath) . " bytes");
            
            // Prepare title and description
            $title = $this->option('title') ?: $track->title;
            $description = $this->option('description') ?: "Generated with SunoPanel\nTrack: {$track->title}";
            
            if (!empty($track->genres_string)) {
                $description .= "\nGenres: {$track->genres_string}";
            }
            
            // Prepare tags
            $tagsString = $this->option('tags');
            $genres = $track->genres()->pluck('name')->toArray();
            $tags = $tagsString ? array_map('trim', explode(',', $tagsString)) : [];
            $tags = array_merge($tags, $genres, ['sunopanel', 'ai music', 'ai generated']);
            
            // Get the privacy setting
            $privacy = $this->getPrivacySetting();
            
            // Upload the video
            $this->info("Uploading track #{$trackId} to YouTube...");
            $this->info("Title: {$title}");
            $this->info("Privacy: {$privacy}");
            
            $videoId = $this->youtubeService->uploadVideo(
                $videoPath,
                $title,
                $description,
                $tags,
                $privacy,
                '10' // Music category
            );
            
            if (!$videoId) {
                $this->error("Upload failed. No video ID returned.");
                return 1;
            }
            
            // Update the track with the YouTube ID
            $track->youtube_video_id = $videoId;
            $track->youtube_uploaded_at = now();
            $track->save();
            
            $this->info("Upload successful! Video ID: {$videoId}");
            $this->info("Video URL: https://www.youtube.com/watch?v={$videoId}");
            
            // Add to playlist if specified
            $playlistName = $this->option('playlist');
            if ($playlistName) {
                $this->addToPlaylist($videoId, $playlistName, $track);
            }
            
            // Add to genre playlists if track has genres
            if ($track->genres->isNotEmpty()) {
                $this->addToGenrePlaylists($videoId, $track);
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Upload failed: " . $e->getMessage());
            Log::error("YouTube upload failed: " . $e->getMessage(), [
                'track_id' => $trackId,
                'exception' => $e,
            ]);
            return 1;
        }
    }
    
    /**
     * Upload a file directly
     *
     * @param string $filePath
     * @return int
     */
    protected function uploadFile($filePath)
    {
        try {
            // Check if file exists
            if (!File::exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return 1;
            }
            
            // Check if file is an MP4
            if (strtolower(File::extension($filePath)) !== 'mp4') {
                $this->error("File must be an MP4: {$filePath}");
                return 1;
            }
            
            // Get title from option or filename
            $title = $this->option('title') ?: File::name($filePath);
            $description = $this->option('description') ?: '';
            
            // Prepare tags
            $tagsString = $this->option('tags');
            $tags = $tagsString ? array_map('trim', explode(',', $tagsString)) : [];
            
            // Get the privacy setting
            $privacy = $this->getPrivacySetting();
            
            $this->info("Uploading {$filePath} to YouTube...");
            $this->info("Title: {$title}");
            $this->info("Privacy: {$privacy}");
            
            $videoId = $this->youtubeService->uploadVideo(
                $filePath,
                $title,
                $description,
                $tags,
                $privacy,
                '10' // Music category
            );
            
            if (!$videoId) {
                $this->error("Upload failed. No video ID returned.");
                return 1;
            }
            
            $this->info("Upload successful! Video ID: {$videoId}");
            $this->info("Video URL: https://www.youtube.com/watch?v={$videoId}");
            
            // Add to playlist if specified
            $playlistName = $this->option('playlist');
            if ($playlistName) {
                $this->addToPlaylist($videoId, $playlistName);
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Upload failed: " . $e->getMessage());
            Log::error("YouTube upload failed: " . $e->getMessage(), [
                'file' => $filePath,
                'exception' => $e,
            ]);
            return 1;
        }
    }
    
    /**
     * Add a video to a specific playlist
     *
     * @param string $videoId
     * @param string $playlistName
     * @param Track|null $track
     * @return void
     */
    protected function addToPlaylist(string $videoId, string $playlistName, ?Track $track = null)
    {
        try {
            $this->info("Adding video to playlist: {$playlistName}");
            
            $playlistId = $this->youtubeService->findOrCreatePlaylist(
                $playlistName,
                "SunoPanel playlist - {$playlistName}",
                'public'
            );
            
            if (!$playlistId) {
                $this->warn("Could not find or create playlist: {$playlistName}");
                return;
            }
            
            $success = $this->youtubeService->addVideoToPlaylist($videoId, $playlistId);
            
            if ($success) {
                $this->info("Added video to playlist: {$playlistName}");
                
                // Update track with playlist ID if provided
                if ($track) {
                    $track->youtube_playlist_id = $playlistId;
                    $track->save();
                }
            } else {
                $this->warn("Failed to add video to playlist: {$playlistName}");
            }
        } catch (\Exception $e) {
            $this->warn("Error adding video to playlist: " . $e->getMessage());
            Log::error("Failed to add video to playlist: " . $e->getMessage(), [
                'playlist' => $playlistName,
                'video_id' => $videoId,
                'exception' => $e,
            ]);
        }
    }
    
    /**
     * Add a video to genre-based playlists
     *
     * @param string $videoId
     * @param Track $track
     * @return void
     */
    protected function addToGenrePlaylists(string $videoId, Track $track)
    {
        foreach ($track->genres as $genre) {
            $playlistName = "SunoPanel - {$genre->name}";
            $this->addToPlaylist($videoId, $playlistName, $track);
        }
    }
} 