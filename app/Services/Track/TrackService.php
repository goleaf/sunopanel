<?php

declare(strict_types=1);

namespace App\Services\Track;

use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class TrackService
{
    /**
     * Store a new track
     */
    public function store(TrackStoreRequest $request): Track
    {
        $validatedData = $request->validated();
        
        // Handle file upload if provided
        if (isset($validatedData['file']) && $validatedData['file'] instanceof UploadedFile) {
            $filePath = $this->storeTrackFile($validatedData['file']);
            $validatedData['file_path'] = $filePath;
            unset($validatedData['file']);
        }
        
        // Create the track
        $track = Track::create([
            'title' => $validatedData['title'],
            'artist' => $validatedData['artist'] ?? null,
            'album' => $validatedData['album'] ?? null,
            'year' => $validatedData['year'] ?? null,
            'file_path' => $validatedData['file_path'] ?? null,
            'genre_id' => $validatedData['genre_id'] ?? null,
            'duration' => $validatedData['duration'] ?? null,
            'description' => $validatedData['description'] ?? null,
        ]);
        
        Log::info('Track created successfully', [
            'track_id' => $track->id, 
            'title' => $track->title
        ]);
        
        return $track;
    }
    
    /**
     * Update an existing track
     */
    public function update(TrackUpdateRequest $request, Track $track): Track
    {
        $validatedData = $request->validated();
        
        // Handle file upload if provided
        if (isset($validatedData['file']) && $validatedData['file'] instanceof UploadedFile) {
            // Delete old file if exists
            if ($track->file_path) {
                Storage::disk('private')->delete($track->file_path);
            }
            
            $filePath = $this->storeTrackFile($validatedData['file']);
            $validatedData['file_path'] = $filePath;
            unset($validatedData['file']);
        }
        
        // Update the track
        $track->update([
            'title' => $validatedData['title'] ?? $track->title,
            'artist' => $validatedData['artist'] ?? $track->artist,
            'album' => $validatedData['album'] ?? $track->album,
            'year' => $validatedData['year'] ?? $track->year,
            'file_path' => $validatedData['file_path'] ?? $track->file_path,
            'genre_id' => $validatedData['genre_id'] ?? $track->genre_id,
            'duration' => $validatedData['duration'] ?? $track->duration,
            'description' => $validatedData['description'] ?? $track->description,
        ]);
        
        Log::info('Track updated successfully', [
            'track_id' => $track->id, 
            'title' => $track->title
        ]);
        
        return $track;
    }
    
    /**
     * Delete a track
     */
    public function delete(Track $track): bool
    {
        // Check if the track is associated with any playlists
        $playlistCount = $track->playlists()->count();
        
        if ($playlistCount > 0) {
            // Detach from all playlists first
            $track->playlists()->detach();
            Log::info('Track detached from playlists before deletion', [
                'track_id' => $track->id,
                'playlist_count' => $playlistCount
            ]);
        }
        
        // Delete the file if it exists
        if ($track->file_path) {
            Storage::disk('private')->delete($track->file_path);
            Log::info('Track file deleted', [
                'track_id' => $track->id,
                'file_path' => $track->file_path
            ]);
        }
        
        // Delete the track
        $deleted = $track->delete();
        
        Log::info('Track deleted', [
            'track_id' => $track->id, 
            'title' => $track->title,
            'success' => $deleted
        ]);
        
        return $deleted;
    }
    
    /**
     * Get tracks by genre
     */
    public function getByGenre(Genre $genre, int $limit = 20, int $offset = 0): array
    {
        $tracks = $genre->tracks()->skip($offset)->take($limit)->get();
        
        Log::info('Retrieved tracks by genre', [
            'genre_id' => $genre->id,
            'track_count' => $tracks->count(),
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        return $tracks->toArray();
    }
    
    /**
     * Get tracks with their genres
     */
    public function getWithGenres(int $limit = 20, int $offset = 0): array
    {
        $tracks = Track::with('genre')->skip($offset)->take($limit)->get();
        
        Log::info('Retrieved tracks with genres', [
            'track_count' => $tracks->count(),
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        return $tracks->toArray();
    }
    
    /**
     * Store a track file and return the file path
     */
    private function storeTrackFile(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/tracks', $filename, 'private');
        
        Log::info('Track file stored', [
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path
        ]);
        
        return $path;
    }
} 