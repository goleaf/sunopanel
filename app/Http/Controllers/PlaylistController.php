<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PlaylistCreateFromGenreRequest;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Services\Logging\LoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlaylistController extends Controller
{
    private LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Display a listing of the playlists.
     */
    public function index(Request $request): View
    {
        try {
            $this->loggingService->info('Playlist index accessed', [
                'query' => $request->query(),
                'user_id' => auth()->id(),
            ]);

            $query = Playlist::query()->withCount('tracks')->with('genre');

            // Handle search
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");

                $this->loggingService->info('Playlist search applied', ['term' => $searchTerm]);
            }

            // Handle sorting
            if ($request->has('sort')) {
                $sortField = $request->sort;
                $direction = $request->direction ?? 'asc';

                // Validate sort direction
                if (! in_array($direction, ['asc', 'desc'])) {
                    $direction = 'asc';
                }

                // Validate sort field
                $allowedSortFields = ['title', 'created_at', 'tracks_count'];
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $direction);

                    $this->loggingService->info('Playlist sort applied', [
                        'field' => $sortField,
                        'direction' => $direction,
                    ]);
                }
            } else {
                // Default sorting
                $query->orderBy('created_at', 'desc');
            }

            $playlists = $query->paginate(10)->withQueryString();

            return view('playlists.index', compact('playlists'));
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@index');

            return view('playlists.index', [
                'playlists' => collect(),
                'error' => 'An error occurred while loading playlists.',
            ]);
        }
    }

    /**
     * Show the form for creating a new playlist.
     */
    public function create(): View
    {
        $this->loggingService->info('Playlist create form accessed');
        $genres = Genre::orderBy('name')->get();

        return view('playlists.create', compact('genres'));
    }

    /**
     * Store a newly created playlist in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->loggingService->info('Playlist store method called', ['request' => $request->except(['_token'])]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|url',
            'genre_id' => 'nullable|exists:genres,id',
            'track_ids' => 'nullable|array',
            'track_ids.*' => 'exists:tracks,id',
        ]);

        try {
            $playlist = Playlist::create($validated);

            // Attach tracks if provided
            if ($request->has('track_ids')) {
                $position = 0;
                foreach ($request->track_ids as $trackId) {
                    $playlist->tracks()->attach($trackId, ['position' => $position]);
                    $position++;
                }
            }

            $this->loggingService->info('Playlist created successfully', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist created successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@store');

            return redirect()->back()
                ->with('error', 'Failed to create playlist: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified playlist.
     */
    public function show(Request $request, Playlist $playlist): View
    {
        $this->loggingService->info('Playlist show page accessed', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

        $playlist->load(['tracks.genres', 'genre']);

        return view('playlists.show', compact('playlist'));
    }

    /**
     * Show the form for editing the specified playlist.
     */
    public function edit(Playlist $playlist): View
    {
        $this->loggingService->info('Playlist edit form accessed', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

        $genres = Genre::orderBy('name')->get();

        return view('playlists.edit', compact('playlist', 'genres'));
    }

    /**
     * Update the specified playlist in storage.
     */
    public function update(Request $request, Playlist $playlist): RedirectResponse
    {
        $this->loggingService->info('Playlist update method called', [
            'playlist_id' => $playlist->id,
            'title' => $playlist->title,
            'request' => $request->except(['_token']),
        ]);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'genre_id' => 'nullable|exists:genres,id',
        ]);

        // For backward compatibility: if name is set but title isn't, use name as title
        if (! isset($validated['title']) && isset($validated['name'])) {
            $validated['title'] = $validated['name'];
        }

        // Make sure title is set (required)
        if (empty($validated['title'])) {
            return redirect()->back()
                ->withErrors(['title' => 'The title field is required.'])
                ->withInput();
        }

        try {
            // Remove 'name' from the validated array to prevent unknown column error
            if (isset($validated['name'])) {
                unset($validated['name']);
            }

            $playlist->update($validated);

            // Update cover image if provided
            if ($request->hasFile('cover_image')) {
                $file = $request->file('cover_image');
                $filename = time().'_'.$file->getClientOriginalName();
                $file->storeAs('public/covers', $filename);
                $playlist->update(['cover_image' => 'covers/'.$filename]);
            }

            $this->loggingService->info('Playlist updated successfully', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist updated successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@update', $playlist->id);

            return redirect()->back()
                ->with('error', 'Failed to update playlist: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified playlist from storage.
     */
    public function destroy(Playlist $playlist): RedirectResponse
    {
        try {
            $this->loggingService->info('Playlist delete initiated', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

            $playlist->delete();

            $this->loggingService->info('Playlist deleted successfully', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist deleted successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, request(), 'PlaylistController@destroy', $playlist->id);

            return redirect()->back()
                ->with('error', 'Failed to delete playlist: '.$e->getMessage());
        }
    }

    /**
     * Show form to add tracks to the playlist.
     */
    public function addTracks(Playlist $playlist): View
    {
        $this->loggingService->info('Add tracks to playlist form accessed', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

        $playlist->load('tracks');
        $existingTrackIds = $playlist->tracks->pluck('id')->toArray();

        // Get tracks not in this playlist
        $tracks = Track::with('genres')
            ->whereNotIn('id', $existingTrackIds)
            ->orderBy('title')
            ->paginate(20);

        // Get all genres for filtering
        $genres = Genre::orderBy('name')->get();

        // Add available tracks for tests that check for this variable
        $availableTracks = $tracks;

        return view('playlists.add-tracks', compact('playlist', 'tracks', 'genres', 'availableTracks'));
    }

    /**
     * Store tracks in the playlist.
     */
    public function storeTracks(Request $request, Playlist $playlist): RedirectResponse
    {
        $this->loggingService->info('Adding tracks to playlist', [
            'playlist_id' => $playlist->id,
            'playlist_title' => $playlist->title,
            'track_count' => count($request->input('track_ids', [])),
        ]);

        $request->validate([
            'track_ids' => 'required|array',
            'track_ids.*' => 'exists:tracks,id',
        ]);

        try {
            $trackIds = $request->input('track_ids', []);
            $position = $playlist->tracks()->count(); // Start position after existing tracks

            foreach ($trackIds as $trackId) {
                // Check if track is already in playlist
                if (! $playlist->tracks()->where('track_id', $trackId)->exists()) {
                    $playlist->tracks()->attach($trackId, ['position' => $position]);
                    $position++;
                }
            }

            $this->loggingService->info('Tracks added to playlist successfully', [
                'playlist_id' => $playlist->id,
                'track_count' => count($trackIds),
            ]);

            return redirect()->route('playlists.show', $playlist->id)
                ->with('success', count($trackIds).' tracks added to playlist successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@storeTracks', $playlist->id);

            return redirect()->back()
                ->with('error', 'Failed to add tracks to playlist: '.$e->getMessage());
        }
    }

    /**
     * Remove a track from the playlist.
     */
    public function removeTrack(Playlist $playlist, Track $track): RedirectResponse
    {
        try {
            $this->loggingService->info('Removing track from playlist', [
                'playlist_id' => $playlist->id,
                'playlist_title' => $playlist->title,
                'track_id' => $track->id,
                'track_title' => $track->title,
            ]);

            // Detach the track from the playlist
            $playlist->tracks()->detach($track->id);

            // Reorder the remaining tracks to ensure position integrity
            $positions = 0;
            foreach ($playlist->tracks as $trackItem) {
                $playlist->tracks()->updateExistingPivot($trackItem->id, ['position' => $positions]);
                $positions++;
            }

            $this->loggingService->info('Track removed from playlist successfully', [
                'playlist_id' => $playlist->id,
                'track_id' => $track->id,
            ]);

            return redirect()->route('playlists.show', $playlist->id)
                ->with('success', 'Track removed from playlist successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, request(), 'PlaylistController@removeTrack', $playlist->id);

            return redirect()->back()
                ->with('error', 'Failed to remove track from playlist: '.$e->getMessage());
        }
    }

    /**
     * Create a playlist from a genre.
     */
    public function createFromGenre(PlaylistCreateFromGenreRequest $request, Genre $genre): RedirectResponse
    {
        try {
            $this->loggingService->info('Creating playlist from genre', [
                'genre_id' => $genre->id,
                'genre_name' => $genre->name,
            ]);

            // Get tracks from this genre (limited to 50 to prevent too large playlists)
            $tracks = $genre->tracks()->inRandomOrder()->limit(50)->get();

            if ($tracks->isEmpty()) {
                return redirect()->route('genres.show', $genre->id)
                    ->with('warning', 'No tracks found in this genre to create playlist.');
            }

            // Get title suffix if provided, otherwise use "Playlist"
            $titleSuffix = $request->input('title_suffix') ? $request->input('title_suffix') : ' Playlist';

            // Create a new playlist
            $playlist = Playlist::create([
                'title' => $genre->name.$titleSuffix,
                'description' => 'Auto-generated playlist from '.$genre->name.' genre.',
                'genre_id' => $genre->id,
            ]);

            // Add tracks to playlist
            $position = 0;
            foreach ($tracks as $track) {
                $playlist->tracks()->attach($track->id, ['position' => $position]);
                $position++;
            }

            $this->loggingService->info('Playlist created from genre successfully', [
                'playlist_id' => $playlist->id,
                'genre_id' => $genre->id,
                'track_count' => $tracks->count(),
            ]);

            return redirect()->route('playlists.show', $playlist->id)
                ->with('success', 'Playlist created from genre with '.$tracks->count().' tracks.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, request(), 'PlaylistController@createFromGenre', $genre->id);

            return redirect()->back()
                ->with('error', 'Failed to create playlist from genre: '.$e->getMessage());
        }
    }
}
