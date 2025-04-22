<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class YouTubePlaylistManager
{
    protected YouTubeService $youtubeService;
    
    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }
    
    /**
     * Find or create a playlist
     * 
     * @param string $title Title of the playlist
     * @param string $description Description of the playlist
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @return string|null Playlist ID if successful, null if failed
     */
    public function findOrCreatePlaylist(
        string $title,
        string $description = '',
        string $privacyStatus = 'public'
    ): ?string {
        return $this->youtubeService->findOrCreatePlaylist($title, $description, $privacyStatus);
    }
    
    /**
     * Add a video to a playlist
     * 
     * @param string $videoId YouTube video ID
     * @param string $playlistId YouTube playlist ID
     * @return bool True if successful, false if failed
     */
    public function addVideoToPlaylist(string $videoId, string $playlistId): bool
    {
        return $this->youtubeService->addVideoToPlaylist($videoId, $playlistId);
    }
    
    /**
     * Get all playlists for the authenticated user
     * 
     * @return array Associative array of playlist ID => playlist title
     */
    public function getPlaylists(): array
    {
        return $this->youtubeService->getPlaylists();
    }
    
    /**
     * Find a playlist by title
     * 
     * @param string $title Title of the playlist to find
     * @return string|null Playlist ID if found, null if not found
     */
    public function findPlaylistByTitle(string $title): ?string
    {
        return $this->youtubeService->findPlaylistByTitle($title);
    }
    
    /**
     * Create a new playlist
     * 
     * @param string $title Title of the playlist
     * @param string $description Description of the playlist
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @return string|null Playlist ID if successful, null if failed
     */
    public function createPlaylist(
        string $title,
        string $description = '',
        string $privacyStatus = 'public'
    ): ?string {
        return $this->youtubeService->createPlaylist($title, $description, $privacyStatus);
    }
} 