<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\Genre;
use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Http\Requests\TrackDeleteRequest;
use App\Http\Requests\BulkTrackRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Playlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TrackController extends Controller
{
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
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhereHas('genres', function($q) use ($searchTerm) {
                         $q->where('name', 'like', "%{$searchTerm}%");
                      });
                });
            }
            
            // Genre filter
            if ($request->has('genre') && $request->genre) {
                $query->whereHas('genres', function($q) use ($request) {
                    $q->where('genres.id', $request->genre);
                });
            }
            
            // Sorting
            $sortField = $request->sort ?? 'title';
            $direction = $request->direction ?? 'asc';
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['title', 'created_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'title';
            }
            
            // Validate sort direction
            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = 'asc';
            }
            
            $query->orderBy($sortField, $direction);
            
            $tracks = $query->paginate(15)->withQueryString();
            
            // Get genres for the filter dropdown
            $genres = Genre::orderBy('name')->get();
            
            Log::info('Tracks index page accessed', [
                'search' => $request->search,
                'genre' => $request->genre,
                'sort' => $sortField,
                'direction' => $direction,
                'count' => $tracks->count()
            ]);
            
            return view('tracks.index', compact('tracks', 'genres'));
        } catch (\Exception $e) {
            Log::error('Error in TrackController@index', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('tracks.index', [
                'tracks' => collect(),
                'genres' => Genre::orderBy('name')->get(),
                'error' => 'An error occurred while loading tracks.'
            ]);
        }
    }

    /**
     * Show the form for creating a new track.
     */
    public function create(): View
    {
        Log::info('Track create form accessed');
        $genres = Genre::orderBy('name')->get();
        return view('tracks.create', compact('genres'));
    }

    /**
     * Store a newly created track in storage.
     */
    public function store(TrackStoreRequest $request): RedirectResponse
    {
        try {
            Log::info('TrackController: store method called', ['request' => $request->validated()]);
            
            // Check if this is a bulk upload request
            if ($request->has('bulk_tracks') && !empty($request->validated('bulk_tracks'))) {
                return $this->processBulkUpload($request);
            }
            
            $track = Track::create([
                'title' => $request->validated('title'),
                'audio_url' => $request->validated('audio_url'),
                'image_url' => $request->validated('image_url'),
                'duration' => $request->validated('duration'),
                'unique_id' => Track::generateUniqueId($request->validated('title')),
            ]);
            
            if ($request->has('genres')) {
                $track->syncGenres($request->validated('genres'));
            }
            
            if ($request->has('playlists')) {
                $track->playlists()->attach($request->validated('playlists'));
            }
            
            Log::info('TrackController: track created successfully', ['track_id' => $track->id]);
            
            return redirect()->route('tracks.index')
                ->with('success', 'Track created successfully.');
        } catch (\Exception $e) {
            Log::error('TrackController: Error creating track', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create track: ' . $e->getMessage());
        }
    }

    /**
     * Process bulk upload of tracks.
     */
    public function processBulkUpload(BulkTrackRequest $request): RedirectResponse
    {
        Log::info('Bulk track upload initiated', ['lines_count' => substr_count($request->validated('bulk_tracks'), PHP_EOL) + 1]);

        $bulkText = $request->validated('bulk_tracks');
        $lines = explode(PHP_EOL, $bulkText);
        $processedCount = 0;
        $errors = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            try {
                $parts = explode('|', $line);
                if (count($parts) < 4) {
                    $errors[] = "Line " . ($index + 1) . ": Invalid format - expected at least 4 parts separated by |";
                    continue;
                }

                $title = trim($parts[0]);
                $audioUrl = trim($parts[1]);
                $imageUrl = trim($parts[2]);
                $genresRaw = trim($parts[3]);
                $duration = isset($parts[4]) ? trim($parts[4]) : '3:00';

                // Skip if track with this title already exists
                if (Track::where('title', $title)->exists()) {
                    $errors[] = "Line " . ($index + 1) . ": Track '$title' already exists";
                    continue;
                }

                // Create track
                $track = Track::create([
                    'title' => $title,
                    'audio_url' => $audioUrl,
                    'image_url' => $imageUrl,
                    'unique_id' => Track::generateUniqueId($title),
                    'duration' => $duration
                ]);

                // Sync genres
                $track->syncGenres($genresRaw);

                $processedCount++;
                Log::info('Bulk track created', ['index' => $index, 'title' => $title, 'track_id' => $track->id]);
            } catch (\Exception $e) {
                $errors[] = "Line " . ($index + 1) . ": Error - " . $e->getMessage();
                Log::error('Bulk track import error', [
                    'line' => $line,
                    'line_number' => $index + 1,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk track upload completed', [
            'processed' => $processedCount,
            'errors' => count($errors)
        ]);

        if ($processedCount > 0) {
            $message = "$processedCount tracks imported successfully!";
            if (count($errors) > 0) {
                $message .= " There were " . count($errors) . " errors.";
            }
            return redirect()->route('tracks.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No tracks were imported. Please check the errors.')
                ->with('import_errors', $errors);
        }
    }

    /**
     * Display the specified track.
     */
    public function show(int $id): View
    {
        try {
            $track = Track::with('genres', 'playlists')->findOrFail($id);
            Log::info('Track viewed', ['id' => $id, 'title' => $track->title]);
            return view('tracks.show', compact('track'));
        } catch (\Exception $e) {
            Log::error('Error viewing track', ['id' => $id, 'error' => $e->getMessage()]);
            abort(404);
        }
    }

    /**
     * Show the form for editing the specified track.
     */
    public function edit(Track $track): View
    {
        Log::info('Track edit form accessed', ['id' => $track->id, 'title' => $track->title]);
        $genres = Genre::orderBy('name')->get();
        $trackGenres = $track->genres->pluck('id')->toArray();
        return view('tracks.edit', compact('track', 'genres', 'trackGenres'));
    }

    /**
     * Update the specified track in storage.
     */
    public function update(TrackUpdateRequest $request, Track $track): RedirectResponse
    {
        try {
            Log::info('TrackController: update method called', [
                'track_id' => $track->id,
                'request' => $request->validated()
            ]);
            
            $track->update($request->validated());
            
            if ($request->has('genres')) {
                $track->syncGenres($request->validated('genres'));
            }
            
            if ($request->has('playlists')) {
                $track->playlists()->sync($request->validated('playlists'));
            }
            
            Log::info('TrackController: track updated successfully', ['track_id' => $track->id]);
            
            return redirect()->route('tracks.index')
                ->with('success', 'Track updated successfully.');
        } catch (\Exception $e) {
            Log::error('TrackController: Error updating track', [
                'track_id' => $track->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update track: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified track from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $track = Track::findOrFail($id);
            Log::info('TrackController: destroy method called', ['track_id' => $id, 'title' => $track->title]);
            
            // Detach from any playlists
            $track->playlists()->detach();
            
            // Detach from genres
            $track->genres()->detach();
            
            // Delete the track
            $track->delete();
            
            Log::info('TrackController: track deleted successfully', ['track_id' => $id]);
            
            return redirect()->route('tracks.index')
                ->with('success', 'Track deleted successfully.');
        } catch (\Exception $e) {
            Log::error('TrackController: Error deleting track', [
                'track_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to delete track: ' . $e->getMessage());
        }
    }

    /**
     * Play the specified track.
     */
    public function play(int $id): RedirectResponse
    {
        try {
            $track = Track::findOrFail($id);
            Log::info('Track played', ['id' => $id, 'title' => $track->title]);
            
            // Check url first (new field), then audio_url (old field)
            $audioUrl = $track->url ?? $track->audio_url;
            
            if (empty($audioUrl)) {
                return redirect()->back()
                    ->with('error', 'Track has no audio URL');
            }
            
            return redirect()->away($audioUrl);
        } catch (\Exception $e) {
            Log::error('Error playing track', ['id' => $id, 'error' => $e->getMessage()]);
            abort(404);
        }
    }
}
