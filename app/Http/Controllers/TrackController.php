<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackDeleteRequest;
use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Track;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TrackController extends Controller
{
    /**
     * Store a newly created track.
     */
    public function store(TrackStoreRequest $request)
    {
        $validated = $request->validated();
        
        try {
            DB::transaction(function () use ($validated) {
                // Create the track
                $track = Track::create([
                    'title' => $validated['title'],
                    'artist' => $validated['artist'] ?? null,
                    'album' => $validated['album'] ?? null,
                    'duration' => $validated['duration'] ?? null,
                    'audio_url' => $validated['audio_url'],
                    'image_url' => $validated['image_url'] ?? null,
                    'unique_id' => Track::generateUniqueId($validated['title']),
                ]);
                
                // Attach genres if provided
                if (isset($validated['genres']) || isset($validated['genre_ids'])) {
                    $genreIds = $validated['genre_ids'] ?? $validated['genres'] ?? [];
                    $track->genres()->sync(Arr::wrap($genreIds));
                }
                
                // Attach to playlists if provided
                if (isset($validated['playlists'])) {
                    $playlists = Arr::wrap($validated['playlists']);
                    foreach ($playlists as $playlistId) {
                        // Get the max position in this playlist and add 1
                        $maxPosition = DB::table('playlist_track')
                            ->where('playlist_id', $playlistId)
                            ->max('position') ?? 0;
                        
                        $track->playlists()->attach($playlistId, ['position' => $maxPosition + 1]);
                    }
                }
            });
            
            Log::info('Track created', [
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('tracks.index')
                ->with('success', 'Track created successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating track', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->back()
                ->with('error', 'Error creating track: ' . $e->getMessage());
        }
    }
    
    /**
     * Update the specified track.
     */
    public function update(TrackUpdateRequest $request, Track $track)
    {
        $validated = $request->validated();
        
        try {
            DB::transaction(function () use ($track, $validated) {
                // Update the track
                $track->update([
                    'title' => $validated['title'],
                    'artist' => $validated['artist'] ?? $track->artist,
                    'album' => $validated['album'] ?? $track->album,
                    'duration' => $validated['duration'] ?? $track->duration,
                    'audio_url' => $validated['audio_url'] ?? $track->audio_url,
                    'image_url' => $validated['image_url'] ?? $track->image_url,
                ]);
                
                // Update genres if provided
                if (isset($validated['genres']) || isset($validated['genre_ids'])) {
                    $genreIds = $validated['genre_ids'] ?? $validated['genres'] ?? [];
                    $track->genres()->sync(Arr::wrap($genreIds));
                }
            });
            
            Log::info('Track updated', [
                'track_id' => $track->id,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('tracks.index')
                ->with('success', 'Track updated successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error updating track', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->back()
                ->with('error', 'Error updating track: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove the specified track.
     */
    public function destroy(Track $track, TrackDeleteRequest $request)
    {
        try {
            $track->delete();
            
            Log::info('Track deleted', [
                'track_id' => $track->id,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('tracks.index')
                ->with('success', 'Track deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting track', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting track: ' . $e->getMessage());
        }
    }
} 