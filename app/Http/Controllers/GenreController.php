<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class GenreController extends Controller
{
    /**
     * Display a listing of the genres.
     */
    public function index(Request $request): View
    {
        $query = Genre::query()->withCount('tracks');

        // Handle search
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Handle sorting
        $sortField = $request->sort ?? 'name';
        $direction = $request->direction ?? 'asc';
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['name', 'tracks_count', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'name';
        }
        
        $query->orderBy($sortField, $direction === 'desc' ? 'desc' : 'asc');
        
        $genres = $query->paginate(15)->withQueryString();

        Log::info('Genres index page accessed', [
            'search' => $request->search,
            'sort' => $sortField,
            'direction' => $direction,
            'count' => $genres->count()
        ]);

        return view('genres.index', compact('genres'));
    }

    /**
     * Show the form for creating a new genre.
     */
    public function create(): View
    {
        Log::info('Genre create form accessed');
        return view('genres.create');
    }

    /**
     * Store a newly created genre in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('Genre store method called', ['request' => $request->except(['_token'])]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
            'description' => 'nullable|string',
        ]);

        $genre = Genre::create($validated);

        Log::info('Genre created successfully', ['genre_id' => $genre->id, 'name' => $genre->name]);
        
        return redirect()->route('genres.index')
            ->with('success', 'Genre created successfully.');
    }

    /**
     * Display the specified genre.
     */
    public function show(Request $request, Genre $genre): View
    {
        Log::info('Genre show page accessed', ['genre_id' => $genre->id, 'name' => $genre->name]);
        
        $query = $genre->tracks();
        $perPage = $request->input('per_page', 10);
        
        // Sorting for tracks within a genre
        $sortField = $request->query('sort', 'title');
        $sortOrder = $request->query('order', 'asc');
        
        $allowedSortFields = ['title', 'created_at', 'duration'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'title';
        }
        
        $query->orderBy($sortField, $sortOrder);
        
        Log::info('Genre show page tracks sorted', [
            'genre_id' => $genre->id,
            'field' => $sortField, 
            'order' => $sortOrder
        ]);
        
        $tracks = $query->paginate($perPage);
        $tracks->appends($request->query());
        
        return view('genres.show', compact('genre', 'tracks', 'sortField', 'sortOrder'));
    }

    /**
     * Show the form for editing the specified genre.
     */
    public function edit(Genre $genre): View
    {
        Log::info('Genre edit form accessed', ['genre_id' => $genre->id, 'name' => $genre->name]);
        return view('genres.edit', compact('genre'));
    }

    /**
     * Update the specified genre in storage.
     */
    public function update(Request $request, Genre $genre): RedirectResponse
    {
        Log::info('Genre update method called', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'request' => $request->except(['_token'])
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
            'description' => 'nullable|string',
        ]);

        $genre->update($validated);

        Log::info('Genre updated successfully', ['genre_id' => $genre->id, 'name' => $genre->name]);
        
        return redirect()->route('genres.index')
            ->with('success', 'Genre updated successfully.');
    }

    /**
     * Remove the specified genre from storage.
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        Log::info('Genre delete method called', ['genre_id' => $genre->id, 'name' => $genre->name]);
        
        // Get track count for logging
        $trackCount = $genre->tracks()->count();
        
        // Detach tracks instead of deleting them
        $genre->tracks()->detach();
        
        // Now delete the genre
        $genre->delete();

        Log::info('Genre deleted successfully', [
            'genre_id' => $genre->id, 
            'name' => $genre->name,
            'detached_tracks' => $trackCount
        ]);
        
        return redirect()->route('genres.index')
            ->with('success', 'Genre deleted successfully.');
    }
}
