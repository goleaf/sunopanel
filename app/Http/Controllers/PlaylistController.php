<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            Log::info('Playlist index accessed', [
                'query' => $request->query(),
                'user_id' => auth()->id(),
            ]);

            $query = Playlist::query()->withCount('tracks');

            // Handle search
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
                
                Log::info('Playlist search applied', ['term' => $searchTerm]);
            }

            // Handle sorting
            if ($request->has('sort')) {
                $sortField = $request->sort;
                $direction = $request->direction ?? 'asc';
                
                // Validate sort direction
                if (!in_array($direction, ['asc', 'desc'])) {
                    $direction = 'asc';
                }
                
                // Validate sort field
                if (in_array($sortField, ['name', 'created_at', 'tracks_count'])) {
                    $query->orderBy($sortField, $direction);
                    
                    Log::info('Playlist sort applied', [
                        'field' => $sortField,
                        'direction' => $direction
                    ]);
                }
            } else {
                // Default sorting
                $query->orderBy('created_at', 'desc');
            }

            $playlists = $query->paginate(10)->withQueryString();

            return view('playlists.index', compact('playlists'));
        } catch (\Exception $e) {
            Log::error('Error in playlist index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while loading playlists: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Log::info('Playlist create form accessed');
        $genres = Genre::orderBy('name')->get();
        return view('playlists.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Playlist store method called', ['request' => $request->except(['_token'])]);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|url',
            'genre_id' => 'nullable|exists:genres,id',
        ]);

        try {
            $playlist = Playlist::create([
                'name' => $request->name,
                'description' => $request->description,
                'cover_image' => $request->cover_image,
                'genre_id' => $request->genre_id,
            ]);

            Log::info('Playlist created successfully', ['playlist_id' => $playlist->id, 'name' => $playlist->name]);

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating playlist', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to create playlist: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Playlist $playlist)
    {
        Log::info('Playlist show page accessed', ['playlist_id' => $playlist->id, 'name' => $playlist->name]);
        
        $playlist->load(['tracks.genres', 'genre']);
        return view('playlists.show', compact('playlist'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Playlist $playlist)
    {
        Log::info('Playlist edit form accessed', ['playlist_id' => $playlist->id, 'name' => $playlist->name]);
        
        $genres = Genre::orderBy('name')->get();
        return view('playlists.edit', compact('playlist', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Playlist $playlist)
    {
        Log::info('Playlist update method called', [
            'playlist_id' => $playlist->id,
            'name' => $playlist->name,
            'request' => $request->except(['_token'])
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'genre_id' => 'nullable|exists:genres,id',
        ]);

        try {
            $playlist->update([
                'name' => $request->name,
                'description' => $request->description,
                'genre_id' => $request->genre_id,
            ]);

            // Update cover image if provided
            if ($request->hasFile('cover_image')) {
                $file = $request->file('cover_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/covers', $filename);
                $playlist->update(['cover_image' => 'covers/' . $filename]);
            }

            Log::info('Playlist updated successfully', ['playlist_id' => $playlist->id, 'name' => $playlist->name]);
            
            return redirect()->route('playlists.index')
                ->with('success', 'Playlist updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating playlist', [
                'playlist_id' => $playlist->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update playlist: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Playlist $playlist)
    {
        Log::info('Playlist delete method called', ['playlist_id' => $playlist->id, 'name' => $playlist->name]);
        
        try {
            $playlistName = $playlist->name;
            $playlist->delete();

            Log::info('Playlist deleted successfully', ['name' => $playlistName]);

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting playlist', [
                'playlist_id' => $playlist->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('playlists.index')
                ->with('error', 'Failed to delete playlist: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for adding tracks to a playlist.
     *
     * @param  \App\Models\Playlist  $playlist
     * @return \Illuminate\Http\Response
     */
    public function addTracks(Playlist $playlist, Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Accessed PlaylistController@addTracks for playlist: ' . $playlist->id);
        
        $query = Track::with('genres');
        $perPage = $request->input('per_page', 15);
        
        // Apply search filter if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Apply genre filter if provided
        if ($request->has('genre')) {
            $genreId = $request->input('genre');
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }
        
        // Get tracks that don't belong to this playlist
        $playlistTrackIds = $playlist->tracks->pluck('id')->toArray();
        
        // Use paginate instead of get to enable pagination
        $availableTracks = $query->whereNotIn('id', $playlistTrackIds)
            ->orderBy('name')
            ->paginate($perPage);
            
        $availableTracks->appends($request->query());
        
        $genres = Genre::orderBy('name')->get();
        
        return view('playlists.add-tracks', [
            'playlist' => $playlist,
            'availableTracks' => $availableTracks,
            'genres' => $genres,
            'playlistTrackIds' => $playlistTrackIds
        ]);
    }

    /**
     * Store tracks to the playlist.
     */
    public function storeTracks(Request $request, Playlist $playlist)
    {
        Log::info('Playlist store tracks method called', [
            'playlist_id' => $playlist->id,
            'name' => $playlist->name,
            'track_count' => count($request->track_ids ?? [])
        ]);

        $request->validate([
            'track_ids' => 'required|array',
            'track_ids.*' => 'exists:tracks,id',
        ]);

        try {
            $position = $playlist->tracks()->count();
            $addedCount = 0;

            foreach ($request->track_ids as $key => $trackId) {
                $track = Track::findOrFail($trackId);
                $playlist->addTrack($track, $position);
                $position++;
                $addedCount++;
            }

            Log::info('Tracks added to playlist successfully', [
                'playlist_id' => $playlist->id, 
                'name' => $playlist->name,
                'tracks_added' => $addedCount
            ]);

            return redirect()->route('playlists.show', $playlist)
                ->with('success', 'Tracks added to playlist successfully.');
        } catch (\Exception $e) {
            Log::error('Error adding tracks to playlist', [
                'playlist_id' => $playlist->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to add tracks to playlist: ' . $e->getMessage());
        }
    }

    /**
     * Remove a track from the playlist.
     */
    public function removeTrack(Playlist $playlist, Track $track)
    {
        Log::info('Playlist remove track method called', [
            'playlist_id' => $playlist->id, 
            'track_id' => $track->id
        ]);

        try {
            $playlist->removeTrack($track);

            // Re-order positions
            $position = 0;
            foreach ($playlist->tracks()->orderBy('pivot_position')->get() as $index => $t) {
                $playlist->tracks()->updateExistingPivot($t->id, ['position' => $position]);
                $position++;
            }

            Log::info('Track removed from playlist successfully', [
                'playlist_id' => $playlist->id, 
                'track_id' => $track->id
            ]);

            return redirect()->route('playlists.show', $playlist)
                ->with('success', 'Track removed from playlist successfully.');
        } catch (\Exception $e) {
            Log::error('Error removing track from playlist', [
                'playlist_id' => $playlist->id,
                'track_id' => $track->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('playlists.show', $playlist)
                ->with('error', 'Failed to remove track from playlist: ' . $e->getMessage());
        }
    }

    /**
     * Create a playlist from a genre.
     */
    public function createFromGenre(Genre $genre)
    {
        Log::info('Create playlist from genre method called', ['genre_id' => $genre->id, 'name' => $genre->name]);

        try {
            $playlist = Playlist::create([
                'name' => $genre->name . ' Playlist',
                'description' => 'Playlist containing tracks of the ' . $genre->name . ' genre',
                'genre_id' => $genre->id,
            ]);

            $tracksAdded = 0;
            // Add all tracks from the genre to the playlist
            foreach ($genre->tracks as $index => $track) {
                $playlist->addTrack($track, $index);
                $tracksAdded++;
            }

            Log::info('Playlist created from genre successfully', [
                'playlist_id' => $playlist->id,
                'name' => $playlist->name,
                'genre_id' => $genre->id,
                'tracks_added' => $tracksAdded
            ]);

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist created from genre successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating playlist from genre', [
                'genre_id' => $genre->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('genres.show', $genre)
                ->with('error', 'Failed to create playlist from genre: ' . $e->getMessage());
        }
    }
}
