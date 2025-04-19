<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use App\Models\Genre;
use App\Services\Genre\GenreService;
use App\Services\Logging\LoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class GenreController extends Controller
{
    public function __construct(
        private readonly LoggingService $loggingService,
        private readonly GenreService $genreService
    ) {}

    /**
     * Display a listing of the genres.
     */
    public function index(Request $request): View
    {
        try {
            $genres = $this->genreService->getPaginatedGenres($request);

            $this->loggingService->info('Genres index page accessed', [
                'search' => $request->search,
                'sort' => $request->input('sort', 'name'),
                'direction' => $request->input('direction', 'asc'),
                'count' => $genres->count(),
            ]);

            return view('genres.index', [
                'genres' => $genres,
                'sortField' => $request->input('sort', 'name'),
                'direction' => $request->input('direction', 'asc'),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'GenreController@index');

            return view('genres.index', [
                'genres' => collect(),
                'sortField' => 'name',
                'direction' => 'asc',
            ])->with('error', 'An error occurred while loading genres.');
        }
    }

    /**
     * Show the form for creating a new genre.
     */
    public function create(): View
    {
        $this->loggingService->info('Genre create form accessed');
        return view('genres.form');
    }

    /**
     * Store a newly created genre in storage.
     */
    public function store(GenreStoreRequest $request): RedirectResponse
    {
        $this->loggingService->info('Genre store method called', ['request' => $request->validated()]);

        try {
            $genre = $this->genreService->store($request);

            $this->loggingService->info('Genre created successfully via service', ['genre_id' => $genre->id, 'name' => $genre->name]);

            return redirect()->route('genres.index')
                ->with('success', "Genre '{$genre->name}' created successfully.");
        } catch (\Exception $e) {
             $this->loggingService->logError($e, $request, 'GenreController@store');
             return redirect()->back()->withInput()->with('error', 'Failed to create genre: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified genre and its tracks.
     */
    public function show(Request $request, Genre $genre): View
    {
        try {
            $this->loggingService->info('Genre show page accessed', ['genre_id' => $genre->id, 'name' => $genre->name]);

            $tracks = $this->genreService->getPaginatedTracksForGenre($genre, $request);

            $this->loggingService->info('Genre show page tracks retrieved', [
                'genre_id' => $genre->id,
                'field' => $request->query('sort', 'title'),
                'direction' => $request->query('direction', 'asc'),
                'count' => $tracks->count(),
            ]);

            return view('genres.show', [
                'genre' => $genre,
                'tracks' => $tracks,
                'sortField' => $request->query('sort', 'title'),
                'sortOrder' => $request->query('direction', 'asc'),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'GenreController@show', $genre->id);
             return redirect()->route('genres.index')->with('error', 'Genre not found or an error occurred.');
        }
    }

    /**
     * Show the form for editing the specified genre.
     */
    public function edit(Genre $genre): View
    {
        $this->loggingService->info('Genre edit form accessed', ['genre_id' => $genre->id, 'name' => $genre->name]);
        return view('genres.form', compact('genre'));
    }

    /**
     * Update the specified genre in storage.
     */
    public function update(GenreUpdateRequest $request, Genre $genre): RedirectResponse
    {
        $this->loggingService->info('Genre update method called', [
            'genre_id' => $genre->id,
            'request' => $request->validated(),
        ]);

        try {
            $updatedGenre = $this->genreService->update($request, $genre);

            $this->loggingService->info('Genre updated successfully via service', ['genre_id' => $updatedGenre->id, 'name' => $updatedGenre->name]);

            return redirect()->route('genres.index')
                ->with('success', "Genre '{$updatedGenre->name}' updated successfully.");
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'GenreController@update', $genre->id);
            return redirect()->back()->withInput()->with('error', 'Failed to update genre: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified genre from storage.
     */
    public function destroy(Request $request, Genre $genre): RedirectResponse
    {
        $genreName = $genre->name;
        $this->loggingService->info('Genre delete method called', ['genre_id' => $genre->id, 'name' => $genreName]);

        try {
            $deleted = $this->genreService->deleteGenreAndDetachTracks($genre);

            if ($deleted) {
                $this->loggingService->info('Genre deleted successfully via service', ['genre_id' => $genre->id, 'name' => $genreName]);
                return redirect()->route('genres.index')
                    ->with('success', "Genre '{$genreName}' deleted successfully.");
            } else {
                 $this->loggingService->warning('Genre deletion failed via service (e.g., tracks attached)', ['genre_id' => $genre->id, 'name' => $genreName]);
                 return redirect()->route('genres.index')
                     ->with('error', "Failed to delete genre '{$genreName}'. It might have associated tracks.");
            }
        } catch (\Exception $e) {
            $currentRequest = $request ?? request();
            $this->loggingService->logError($e, $currentRequest, 'GenreController@destroy', $genre->id);
            return redirect()->route('genres.index')->with('error', 'Failed to delete genre: ' . $e->getMessage());
        }
    }
}
