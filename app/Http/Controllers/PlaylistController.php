<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaylistDeleteRequest;
use App\Http\Requests\PlaylistCreateFromGenreRequest;
use App\Http\Requests\PlaylistRemoveTrackRequest;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PlaylistController extends Controller
{
    /**
     * Remove a track from a playlist.
     */
    public function removeTrack(Playlist $playlist, Track $track, PlaylistRemoveTrackRequest $request)
    {
        // Validate through form request
        
        try {
            DB::transaction(function () use ($playlist, $track) {
                // Get the position of the track to be removed
                $playlist->tracks()->detach($track->id);
                
                // Reorder remaining tracks
                $remainingTracks = $playlist->tracks()->orderBy('pivot.position')->get();
                
                $position = 1;
                foreach ($remainingTracks as $remainingTrack) {
                    DB::table('playlist_track')
                        ->where('playlist_id', $playlist->id)
                        ->where('track_id', $remainingTrack->id)
                        ->update(['position' => $position]);
                    $position++;
                }
            });
            
            Log::info('Track removed from playlist', [
                'playlist_id' => $playlist->id,
                'track_id' => $track->id,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('playlists.show', $playlist)
                ->with('success', 'Track removed from playlist successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error removing track from playlist', [
                'playlist_id' => $playlist->id,
                'track_id' => $track->id,
                'user_id' => auth()->id() ?? 'guest',
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error removing track from playlist: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new playlist from a genre.
     */
    public function createFromGenre(Genre $genre, PlaylistCreateFromGenreRequest $request)
    {
        $validated = $request->validated();
        
        try {
            DB::transaction(function () use ($genre, $validated) {
                // Create a new playlist
                $playlist = new Playlist();
                $playlist->title = $genre->name . ' ' . ($validated['title_suffix'] ?? 'Playlist');
                $playlist->description = 'Playlist of ' . $genre->name . ' tracks';
                $playlist->genre_id = $genre->id;
                $playlist->save();
                
                // Get all tracks from the genre
                $tracks = $genre->tracks;
                
                // Attach each track to the playlist with position
                $position = 1;
                foreach ($tracks as $track) {
                    $playlist->tracks()->attach($track->id, ['position' => $position]);
                    $position++;
                }
            });
            
            return redirect()->route('playlists.index')
                ->with('success', 'Playlist created from genre successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating playlist from genre', [
                'genre_id' => $genre->id,
                'user_id' => auth()->id() ?? 'guest',
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error creating playlist from genre: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete the specified playlist.
     */
    public function destroy(Playlist $playlist, PlaylistDeleteRequest $request)
    {
        try {
            $playlist->delete();
            
            Log::info('Playlist deleted', [
                'playlist_id' => $playlist->id,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('playlists.index')
                ->with('success', 'Playlist deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting playlist', [
                'playlist_id' => $playlist->id,
                'user_id' => auth()->id() ?? 'guest',
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting playlist: ' . $e->getMessage());
        }
    }
} 