<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BulkTrackRequest;
use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Genre;
use App\Models\Track;
use App\Services\Logging\LoggingService;
use App\Services\Track\TrackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class TrackController extends Controller
{
    public function __construct(
        private readonly LoggingService $loggingService,
        private readonly TrackService $trackService
    ) {}

    /**
     * Display a listing of the tracks.
     */
    public function index(Request $request): View
    {
        try {
            $tracks = $this->trackService->getPaginatedTracks($request);
            $genres = $this->trackService->getGenresForFilter();

            $this->loggingService->info('Tracks index page accessed', [
                'search' => $request->search,
                'genre' => $request->genre,
                'sort' => $request->input('sort', 'title'),
                'direction' => $request->input('direction', 'asc'),
                'count' => $tracks->count(),
            ]);

            return view('tracks.index', [
                'tracks' => $tracks,
                'genres' => $genres,
                'sortField' => $request->input('sort', 'title'),
                'direction' => $request->input('direction', 'asc'),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@index');

            return view('tracks.index', [
                'tracks' => collect(),
                'genres' => $this->trackService->getGenresForFilter(),
                'sortField' => 'title',
                'direction' => 'asc',
            ])->with('error', 'An error occurred while loading tracks.');
        }
    }

    /**
     * Show the form for creating a new track.
     */
    public function create(): View
    {
        $this->loggingService->info('Track create form accessed');
        $genres = $this->trackService->getGenresForFilter();
        $track = null;

        return view('tracks.form', compact('genres', 'track'));
    }

    /**
     * Store a newly created track in storage.
     */
    public function store(TrackStoreRequest $request): RedirectResponse
    {
        try {
            $this->loggingService->info('TrackController: store method called', ['request_data_keys' => array_keys($request->validated())]);

            $track = $this->trackService->storeTrack($request);

            $this->loggingService->info('TrackController: track created successfully via service', ['track_id' => $track->id]);

            return redirect()->route('tracks.index')
                ->with('success', "Track '{$track->title}' created successfully.");
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@store');

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create track: ' . $e->getMessage());
        }
    }

    /**
     * Show the bulk upload form.
     */
    public function showBulkUploadForm(): View
    {
        $this->loggingService->info('Bulk track upload form accessed');
        return view('tracks.bulk-upload');
    }

    /**
     * Process bulk upload of tracks.
     */
    public function processBulkUpload(BulkTrackRequest $request): RedirectResponse
    {
        try {
            $bulkTracksData = $request->validated('bulk_tracks');
            $this->loggingService->info('Bulk track upload initiated', ['lines_count' => substr_count($bulkTracksData, "\n") + 1]);

            [$processedCount, $errors] = $this->trackService->processBulkImport($bulkTracksData, $this->loggingService);

            $message = "Processed {$processedCount} tracks successfully.";
            if (! empty($errors)) {
                $errorMessage = "Bulk upload completed with errors: \n" . implode("\n", $errors);
                $this->loggingService->warning('Bulk upload completed with errors', ['error_count' => count($errors), 'errors' => $errors]);
                return redirect()->route('tracks.bulk-upload.form')
                    ->with('warning', $message)
                    ->with('bulk_errors', $errors);
            } else {
                $this->loggingService->info('Bulk upload completed successfully', ['processed_count' => $processedCount]);
                return redirect()->route('tracks.index')
                    ->with('success', $message);
            }
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@processBulkUpload');
            return redirect()->route('tracks.bulk-upload.form')
                ->with('error', 'An unexpected error occurred during bulk upload: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified track.
     */
    public function show(Track $track): View
    {
        try {
            $this->loggingService->info('Track show page accessed', ['track_id' => $track->id, 'title' => $track->title]);
            $track->load(['genres', 'playlists']);

            return view('tracks.show', compact('track'));
        } catch (\Exception $e) {
            $this->loggingService->logError($e, request(), 'TrackController@show', $track->id ?? null);
            return redirect()->route('tracks.index')->with('error', 'Track not found or an error occurred.');
        }
    }

    /**
     * Show the form for editing the specified track.
     */
    public function edit(Track $track): View
    {
        $this->loggingService->info('Track edit form accessed', ['track_id' => $track->id, 'title' => $track->title]);
        $genres = $this->trackService->getGenresForFilter();
        $track->load('genres');

        return view('tracks.form', compact('track', 'genres'));
    }

    /**
     * Update the specified track in storage.
     */
    public function update(TrackUpdateRequest $request, Track $track): RedirectResponse
    {
        try {
            $this->loggingService->info('TrackController: update method called', [
                'track_id' => $track->id,
                'request_data_keys' => array_keys($request->validated())
            ]);

            $updatedTrack = $this->trackService->updateTrack($request, $track);

            $this->loggingService->info('TrackController: track updated successfully via service', ['track_id' => $updatedTrack->id]);

            return redirect()->route('tracks.show', $updatedTrack)
                ->with('success', "Track '{$updatedTrack->title}' updated successfully.");
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@update', $track->id);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update track: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified track from storage.
     */
    public function destroy(Request $request, Track $track): RedirectResponse
    {
        $trackTitle = $track->title;
        try {
            $this->loggingService->info('Track delete initiated', ['track_id' => $track->id, 'title' => $trackTitle]);

            $deleted = $this->trackService->deleteTrack($track);

            if ($deleted) {
                $this->loggingService->info('Track deleted successfully via service', ['track_id' => $track->id, 'title' => $trackTitle]);
                return redirect()->route('tracks.index')
                    ->with('success', "Track '{$trackTitle}' deleted successfully.");
            } else {
                $this->loggingService->warning('Track deletion failed via service', ['track_id' => $track->id, 'title' => $trackTitle]);
                return redirect()->route('tracks.index')
                    ->with('error', "Failed to delete track '{$trackTitle}'.");
            }
        } catch (\Exception $e) {
            $this->loggingService->logError($e, $request, 'TrackController@destroy', $track->id);
            return redirect()->route('tracks.index')
                ->with('error', 'An error occurred while deleting the track: ' . $e->getMessage());
        }
    }

    /**
     * Simulate playing a track (example action).
     */
    public function play(Track $track): RedirectResponse
    {
        $this->loggingService->info('Track play action triggered', ['track_id' => $track->id, 'title' => $track->title]);
        return redirect()->back()->with('info', "Playing track '{$track->title}'...");
    }
}
