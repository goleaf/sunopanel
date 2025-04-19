<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PlaylistCreateFromGenreRequest;
use App\Http\Requests\PlaylistRequest;
use App\Http\Requests\PlaylistStoreTracksRequest;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Services\Logging\LoggingService;
use App\Services\Playlist\PlaylistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class PlaylistController extends Controller
{
    public function __construct(
        private readonly LoggingService $loggingService,
        private readonly PlaylistService $playlistService
    ) {}

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

            $playlists = $this->playlistService->getPaginatedPlaylists($request);

            return view('playlists.index', [
                'playlists' => $playlists,
                'sortField' => $request->input('sort', 'created_at'),
                'direction' => $request->input('direction', 'desc'),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@index');

            return view('playlists.index', [
                'playlists' => collect(),
                'sortField' => 'created_at',
                'direction' => 'desc',
            ])->with('error', 'An error occurred while loading playlists.');
        }
    }

    /**
     * Show the form for creating a new playlist.
     */
    public function create(): View
    {
        $this->loggingService->info('Playlist create form accessed');
        $genres = Genre::orderBy('name')->get();

        return view('playlists.form', compact('genres'));
    }

    /**
     * Store a newly created playlist in storage.
     */
    public function store(PlaylistRequest $request): RedirectResponse
    {
        $this->loggingService->info('Playlist store method called', ['request' => $request->validated()]);

        try {
            $playlist = $this->playlistService->storeFromRequest($request, Auth::user());

            $this->loggingService->info('Playlist created successfully via service', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

            return redirect()->route('playlists.addTracks', $playlist)
                ->with('success', "Playlist '{$playlist->title}' created successfully. Now add some tracks!");
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@store');

            return redirect()->back()
                ->with('error', 'Failed to create playlist: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified playlist.
     */
    public function show(Request $request, Playlist $playlist): View
    {
        $this->loggingService->info('Playlist show page accessed', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

        try {
            $playlistWithDetails = $this->playlistService->getPlaylistWithTrackDetails($playlist);

            return view('playlists.show', ['playlist' => $playlistWithDetails]);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@show', $playlist->id);
            return redirect()->route('playlists.index')->with('error', 'Playlist not found or an error occurred.');
        }
    }

    /**
     * Show the form for editing the specified playlist.
     */
    public function edit(Playlist $playlist): View
    {
        $this->loggingService->info('Playlist edit form accessed', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);
        $genres = Genre::orderBy('name')->get();

        return view('playlists.form', compact('playlist', 'genres'));
    }

    /**
     * Update the specified playlist in storage.
     */
    public function update(PlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        $this->loggingService->info('Playlist update method called', [
            'playlist_id' => $playlist->id,
            'request' => $request->validated(),
        ]);

        try {
            $updatedPlaylist = $this->playlistService->updateFromRequest($request, $playlist);

            $this->loggingService->info('Playlist updated successfully via service', ['playlist_id' => $updatedPlaylist->id, 'title' => $updatedPlaylist->title]);

            return redirect()->route('playlists.show', $updatedPlaylist)
                ->with('success', "Playlist '{$updatedPlaylist->title}' updated successfully.");
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@update', $playlist->id);

            return redirect()->back()
                ->with('error', 'Failed to update playlist: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified playlist from storage.
     */
    public function destroy(Request $request, Playlist $playlist): RedirectResponse
    {
        $playlistTitle = $playlist->title;
        $this->loggingService->info('Playlist delete initiated', ['playlist_id' => $playlist->id, 'title' => $playlistTitle]);

        try {
            $deleted = $this->playlistService->deletePlaylistAndDetachTracks($playlist);

            if ($deleted) {
                $this->loggingService->info('Playlist deleted successfully via service', ['playlist_id' => $playlist->id, 'title' => $playlistTitle]);
                return redirect()->route('playlists.index')
                    ->with('success', "Playlist '{$playlistTitle}' deleted successfully.");
            } else {
                $this->loggingService->warning('Playlist deletion failed via service', ['playlist_id' => $playlist->id, 'title' => $playlistTitle]);
                return redirect()->route('playlists.index')
                    ->with('error', "Failed to delete playlist '{$playlistTitle}'.");
            }
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@destroy', $playlist->id);

            return redirect()->route('playlists.index')
                ->with('error', 'Failed to delete playlist: ' . $e->getMessage());
        }
    }

    /**
     * Show form to add tracks to the playlist.
     */
    public function addTracks(Request $request, Playlist $playlist): View
    {
        $this->loggingService->info('Add tracks to playlist form accessed', ['playlist_id' => $playlist->id, 'title' => $playlist->title]);

        try {
            [$playlist, $tracks, $genres] = $this->playlistService->getAvailableTracksForPlaylist($playlist, $request);

            return view('playlists.add-tracks', compact('playlist', 'tracks', 'genres'));
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@addTracks', $playlist->id);
            return redirect()->route('playlists.show', $playlist)->with('error', 'Error loading tracks page.');
        }
    }

    /**
     * Store tracks added to the playlist.
     */
    public function storeTracks(PlaylistStoreTracksRequest $request, Playlist $playlist): RedirectResponse
    {
        $this->loggingService->info('Store tracks to playlist method called', [
            'playlist_id' => $playlist->id,
            'track_ids' => $request->validated()['track_ids'] ?? [],
        ]);

        try {
            $count = $this->playlistService->addTracksFromRequest($request, $playlist);

            if ($count > 0) {
                $this->loggingService->info("{$count} tracks added to playlist via service", ['playlist_id' => $playlist->id]);
                return redirect()->route('playlists.show', $playlist)
                    ->with('success', "{$count} track(s) added to playlist '{$playlist->title}'.");
            } else {
                $this->loggingService->info('No new tracks added to playlist via service', ['playlist_id' => $playlist->id]);
                return redirect()->route('playlists.show', $playlist)
                    ->with('info', 'No new tracks were added.');
            }
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@storeTracks', $playlist->id);
            return redirect()->route('playlists.show', $playlist)
                ->with('error', 'An error occurred while adding tracks: ' . $e->getMessage());
        }
    }

    /**
     * Remove a specific track from the playlist.
     */
    public function removeTrack(Request $request, Playlist $playlist, Track $track): RedirectResponse
    {
        $this->loggingService->info('Remove track from playlist method called', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);

        try {
            $removed = $this->playlistService->removeTrack($playlist, $track);

            if ($removed) {
                $this->loggingService->info('Track removed successfully via service', ['playlist_id' => $playlist->id, 'track_id' => $track->id]);
                return redirect()->route('playlists.show', $playlist)
                    ->with('success', "Track '{$track->title}' removed from playlist.");
            } else {
                $this->loggingService->warning('Failed to remove track via service', ['playlist_id' => $playlist->id, 'track_id' => $track->id]);
                return redirect()->route('playlists.show', $playlist)
                    ->with('error', "Failed to remove track '{$track->title}'.");
            }
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@removeTrack', $playlist->id, ['track_id' => $track->id]);
            return redirect()->route('playlists.show', $playlist)
                ->with('error', 'An error occurred while removing the track: ' . $e->getMessage());
        }
    }

    /**
     * Create a new playlist from a genre.
     */
    public function createFromGenre(PlaylistCreateFromGenreRequest $request, Genre $genre): RedirectResponse
    {
        $this->loggingService->info('Create playlist from genre method called', ['genre_id' => $genre->id, 'name' => $genre->name]);

        try {
            $playlist = $this->playlistService->createFromGenre($genre, Auth::user(), $request->validated());

            $this->loggingService->info('Playlist created from genre successfully via service', [
                'playlist_id' => $playlist->id,
                'title' => $playlist->title,
                'genre_id' => $genre->id,
            ]);

            return redirect()->route('playlists.show', $playlist)
                ->with('success', "Playlist '{$playlist->title}' created from genre '{$genre->name}'.");
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'PlaylistController@createFromGenre', null, ['genre_id' => $genre->id]);
            return redirect()->route('genres.show', $genre)
                ->with('error', 'Failed to create playlist from genre: ' . $e->getMessage());
        }
    }
}
