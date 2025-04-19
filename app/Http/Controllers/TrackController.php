<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BulkTrackRequest;
use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Genre;
use App\Models\Track;
use App\Services\Logging\LoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class TrackController extends Controller
{
    private readonly LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Display a listing of the tracks.
     */
    public function index(Request $request): View
    {
        try {
            $query = Track::with('genres');

            // Search functionality
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                        ->orWhereHas('genres', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            }

            // Genre filter
            if ($request->has('genre') && $request->genre) {
                $query->whereHas('genres', function ($q) use ($request) {
                    $q->where('genres.id', $request->genre);
                });
            }

            // Sorting
            $sortField = $request->sort ?? 'title';
            $direction = $request->direction ?? 'asc';

            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['title', 'created_at'];
            if (! in_array($sortField, $allowedSortFields)) {
                $sortField = 'title';
            }

            // Validate sort direction
            if (! in_array($direction, ['asc', 'desc'])) {
                $direction = 'asc';
            }

            $query->orderBy($sortField, $direction);

            $tracks = $query->paginate(15)->withQueryString();

            // Get genres for the filter dropdown
            $genres = Genre::orderBy('name')->get();

            $this->loggingService->info('Tracks index page accessed', [
                'search' => $request->search,
                'genre' => $request->genre,
                'sort' => $sortField,
                'direction' => $direction,
                'count' => $tracks->count(),
            ]);

            return view('tracks.index', compact('tracks', 'genres', 'sortField', 'direction'));
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@index');

            return view('tracks.index', [
                'tracks' => collect(),
                'genres' => Genre::orderBy('name')->get(),
                'sortField' => 'title',
                'direction' => 'asc',
                'error' => 'An error occurred while loading tracks.',
            ]);
        }
    }

    /**
     * Show the form for creating a new track.
     */
    public function create(): View
    {
        $this->loggingService->info('Track create form accessed');
        $genres = Genre::orderBy('name')->get();

        return view('tracks.create', compact('genres'));
    }

    /**
     * Store a newly created track in storage.
     */
    public function store(TrackStoreRequest $request): RedirectResponse
    {
        try {
            $this->loggingService->info('TrackController: store method called', ['request' => $request->validated()]);

            // Check if this is a bulk upload request
            if ($request->has('bulk_tracks') && ! empty($request->validated('bulk_tracks'))) {
                // Call processBulkUpload directly instead of redirecting
                return $this->processBulkUpload($request);
            }

            $track = Track::create([
                'title' => $request->validated('title'),
                'audio_url' => $request->validated('audio_url'),
                'image_url' => $request->validated('image_url'),
                'duration' => $request->validated('duration'),
                'unique_id' => Track::generateUniqueId($request->validated('title')),
            ]);

            // Handle either genres string or genre_ids array
            if ($request->has('genres') && ! empty($request->validated('genres'))) {
                $track->syncGenres($request->validated('genres'));
            } elseif ($request->has('genre_ids') && ! empty($request->validated('genre_ids'))) {
                $genresCollection = Genre::whereIn('id', $request->validated('genre_ids'))->get();
                $genreNames = $genresCollection->pluck('name')->implode(', ');
                $track->syncGenres($genreNames);
            }

            // Attach playlists if provided
            if ($request->has('playlists') && ! empty($request->validated('playlists'))) {
                $track->playlists()->attach($request->validated('playlists'));
            }

            $this->loggingService->info('TrackController: track created successfully', ['track_id' => $track->id]);

            return redirect()->route('tracks.index')
                ->with('success', 'Track created successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@store');

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create track: '.$e->getMessage());
        }
    }

    /**
     * Process bulk upload of tracks.
     */
    public function processBulkUpload(BulkTrackRequest $request): RedirectResponse
    {
        try {
            // Get bulk tracks data from the request
            $bulkTracks = $request->validated('bulk_tracks');

            if (empty($bulkTracks)) {
                throw new \Exception('No bulk tracks data provided');
            }

            $this->loggingService->info('Bulk track upload initiated', ['lines_count' => substr_count($bulkTracks, PHP_EOL) + 1]);

            $lines = explode(PHP_EOL, $bulkTracks);
            $processedCount = 0;
            $errors = [];

            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                try {
                    // Split only by pipe character, not regex patterns
                    $parts = explode('|', $line);
                    if (count($parts) < 4) {
                        $errors[] = 'Line '.($index + 1).': Invalid format - expected at least 4 parts separated by |';

                        continue;
                    }

                    // Sanitize all input data
                    $title = htmlspecialchars(trim($parts[0]), ENT_QUOTES, 'UTF-8');
                    $audioUrl = filter_var(trim($parts[1]), FILTER_SANITIZE_URL);
                    $imageUrl = filter_var(trim($parts[2]), FILTER_SANITIZE_URL);
                    $genresRaw = htmlspecialchars(trim($parts[3]), ENT_QUOTES, 'UTF-8');
                    $duration = isset($parts[4]) ? htmlspecialchars(trim($parts[4]), ENT_QUOTES, 'UTF-8') : '3:00';

                    // Validate URLs
                    if (! filter_var($audioUrl, FILTER_VALIDATE_URL)) {
                        $errors[] = 'Line '.($index + 1).': Invalid audio URL format';

                        continue;
                    }

                    if (! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $errors[] = 'Line '.($index + 1).': Invalid image URL format';

                        continue;
                    }

                    // Skip if track with this title already exists
                    if (Track::where('title', $title)->exists()) {
                        $errors[] = 'Line '.($index + 1).": Track '$title' already exists";

                        continue;
                    }

                    // Create the track inside a transaction
                    DB::beginTransaction();
                    try {
                        $track = Track::create([
                            'title' => $title,
                            'audio_url' => $audioUrl,
                            'image_url' => $imageUrl,
                            'duration' => $duration,
                            'unique_id' => Track::generateUniqueId($title),
                        ]);

                        // Attach genres
                        $track->syncGenres($genresRaw);

                        DB::commit();
                        $processedCount++;
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errors[] = 'Line '.($index + 1).': Error processing track: '.$e->getMessage();
                        $this->loggingService->logError($e, $request, 'TrackController@processBulkUpload - DB transaction');
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Line '.($index + 1).': Error parsing line: '.$e->getMessage();
                    $this->loggingService->logError($e, $request, 'TrackController@processBulkUpload - Line parsing');
                }
            }

            $message = "Processed $processedCount tracks successfully.";
            if (! empty($errors)) {
                // Log errors and include them in the session
                $this->loggingService->warning('Bulk upload completed with errors', [
                    'processed_count' => $processedCount,
                    'error_count' => count($errors),
                    'errors' => $errors,
                ], $request);

                $message .= ' However, '.count($errors).' errors occurred.';
                session()->flash('bulk_errors', $errors);
            } else {
                $this->loggingService->info('Bulk upload completed successfully', [
                    'processed_count' => $processedCount,
                ], $request);
            }

            return redirect()->route('tracks.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@processBulkUpload');

            return redirect()->route('tracks.index')
                ->with('error', 'Failed to process bulk upload: '.$e->getMessage());
        }
    }

    /**
     * Display the specified track.
     */
    public function show(int $id): View
    {
        try {
            $track = Track::with('genres', 'playlists')->findOrFail($id);
            $this->loggingService->info('Track viewed', ['id' => $id, 'title' => $track->title]);

            return view('tracks.show', compact('track'));
        } catch (\Exception $e) {
            $this->loggingService->logError($e, request(), 'TrackController@show');

            return view('tracks.error', [
                'error' => 'Track not found or an error occurred.',
            ]);
        }
    }

    /**
     * Show the form for editing the specified track.
     */
    public function edit(Track $track): View
    {
        $this->loggingService->info('Track edit form accessed', ['track_id' => $track->id]);

        // Eager load genres to avoid N+1 query
        $track->load('genres');

        // Get all genres for the dropdown
        $genres = Genre::orderBy('name')->get();

        return view('tracks.edit', compact('track', 'genres'));
    }

    /**
     * Update the specified track in storage.
     */
    public function update(TrackUpdateRequest $request, Track $track): RedirectResponse
    {
        try {
            $track->update([
                'title' => $request->validated('title'),
                'audio_url' => $request->validated('audio_url'),
                'image_url' => $request->validated('image_url'),
                'duration' => $request->validated('duration'),
            ]);

            // Handle either genres string or genre_ids array
            if ($request->has('genres') && ! empty($request->validated('genres'))) {
                $track->syncGenres($request->validated('genres'));
            } elseif ($request->has('genre_ids') && ! empty($request->validated('genre_ids'))) {
                $genresCollection = Genre::whereIn('id', $request->validated('genre_ids'))->get();
                $genreNames = $genresCollection->pluck('name')->implode(', ');
                $track->syncGenres($genreNames);
            }

            // Sync playlists if provided
            if ($request->has('playlists')) {
                $track->playlists()->sync($request->validated('playlists'));
            }

            $this->loggingService->info('Track updated', ['id' => $track->id, 'title' => $track->title]);

            return redirect()->route('tracks.index')
                ->with('success', 'Track updated successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@update');

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update track: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified track from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $track = Track::findOrFail($id);
            $this->loggingService->info('TrackController: destroy method called', ['track_id' => $id, 'title' => $track->title]);

            // Detach from any playlists
            $track->playlists()->detach();

            // Detach from genres
            $track->genres()->detach();

            // Delete the track
            $track->delete();

            $this->loggingService->info('TrackController: track deleted successfully', ['track_id' => $id]);

            return redirect()->route('tracks.index')
                ->with('success', 'Track deleted successfully.');
        } catch (\Exception $e) {
            $this->loggingService->logError($e, request(), 'TrackController@destroy');

            return redirect()->back()
                ->with('error', 'Failed to delete track: '.$e->getMessage());
        }
    }

    /**
     * Play the track.
     */
    public function play(int $id): RedirectResponse
    {
        try {
            $track = Track::findOrFail($id);
            $this->loggingService->info('Track played', ['id' => $id, 'title' => $track->title]);

            // Check url first (new field), then audio_url (old field)
            $audioUrl = $track->url ?? $track->audio_url;

            if (empty($audioUrl)) {
                return redirect()->back()
                    ->with('error', 'Track has no audio URL');
            }

            return redirect()->away($audioUrl);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, request(), 'TrackController@play');

            return redirect()->back()
                ->with('error', 'Failed to play track: '.$e->getMessage());
        }
    }
}
