<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Track;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * Display a listing of the genres.
     */
    public function index(Request $request): View
    {
        $query = Genre::withCount('tracks');
        
        // Apply search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Get sort parameters
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        
        // Apply sorting
        $query->orderBy($sortField, $sortDirection);
        
        // Get the paginated results
        $genres = $query->paginate(15)->withQueryString();
        
        // Get statistics for the view - apply global YouTube visibility filter
        $youtubeVisibilityFilter = Setting::get('youtube_visibility_filter', 'all');
        $trackQuery = Track::query();
        
        if ($youtubeVisibilityFilter === 'uploaded') {
            $trackQuery->whereNotNull('youtube_video_id');
        } elseif ($youtubeVisibilityFilter === 'not_uploaded') {
            $trackQuery->whereNull('youtube_video_id');
        }
        
        $statistics = [
            'total_genres' => Genre::count(),
            'total_tracks' => $trackQuery->count(),
            'genres_with_tracks' => Genre::whereHas('tracks', function($q) use ($youtubeVisibilityFilter) {
                if ($youtubeVisibilityFilter === 'uploaded') {
                    $q->whereNotNull('youtube_video_id');
                } elseif ($youtubeVisibilityFilter === 'not_uploaded') {
                    $q->whereNull('youtube_video_id');
                }
            })->count(),
            'genres_without_tracks' => Genre::whereDoesntHave('tracks', function($q) use ($youtubeVisibilityFilter) {
                if ($youtubeVisibilityFilter === 'uploaded') {
                    $q->whereNotNull('youtube_video_id');
                } elseif ($youtubeVisibilityFilter === 'not_uploaded') {
                    $q->whereNull('youtube_video_id');
                }
            })->count(),
        ];
        
        return view('genres.index', compact('genres', 'statistics', 'sortField', 'sortDirection'));
    }

    /**
     * Show the form for creating a new genre.
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * Store a newly created genre in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres',
            'genre_id' => 'nullable|string|max:36',
        ]);

        Genre::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'genre_id' => $validated['genre_id'] ?? null,
        ]);

        return redirect()->route('genres.index')->with('success', 'Genre created successfully');
    }

    /**
     * Display the specified genre.
     */
    public function show(Request $request, Genre $genre): View
    {
        // Apply global YouTube visibility filter to tracks
        $youtubeVisibilityFilter = Setting::get('youtube_visibility_filter', 'all');
        $tracksQuery = $genre->tracks();
        
        if ($youtubeVisibilityFilter === 'uploaded') {
            $tracksQuery->whereNotNull('youtube_video_id');
        } elseif ($youtubeVisibilityFilter === 'not_uploaded') {
            $tracksQuery->whereNull('youtube_video_id');
        }
        
        $tracks = $tracksQuery->paginate(15)->withQueryString();
        
        // Get global settings for the view
        $showYoutubeColumn = Setting::get('show_youtube_column', true);
        
        return view('genres.show', compact('genre', 'tracks', 'showYoutubeColumn', 'youtubeVisibilityFilter'));
    }

    /**
     * Show the form for editing the specified genre.
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * Update the specified genre in storage.
     */
    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
            'genre_id' => 'nullable|string|max:36',
        ]);

        $genre->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'genre_id' => $validated['genre_id'] ?? null,
        ]);

        return redirect()->route('genres.index')->with('success', 'Genre updated successfully');
    }

    /**
     * Remove the specified genre from storage.
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'Genre deleted successfully');
    }
}
