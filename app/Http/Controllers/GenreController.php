<?php
namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GenreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error in GenreController@index', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'An error occurred while loading genres.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Log::info('Genre create form accessed');
        return view('genres.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Genre store method called', ['request' => $request->except(['_token'])]);

        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
            'description' => 'nullable|string',
        ]);

        try {
            $genre = Genre::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            Log::info('Genre created successfully', ['genre_id' => $genre->id, 'name' => $genre->name]);
            
            return redirect()->route('genres.index')
                ->with('success', 'Genre created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating genre', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to create genre: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Genre $genre)
    {
        Log::info('Genre show page accessed', ['genre_id' => $genre->id, 'name' => $genre->name]);
        
        $query = $genre->tracks();
        $perPage = $request->input('per_page', 10);
        
        // Sorting for tracks within a genre
        $sortField = request('sort') ?? 'name';
        $sortOrder = request('order') ?? 'asc';
        
        $allowedSortFields = ['name', 'created_at', 'duration'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'name';
        }
        
        $query->orderBy($sortField, $sortOrder);
        
        Log::info('Genre show page tracks sorted', [
            'genre_id' => $genre->id,
            'field' => $sortField, 
            'order' => $sortOrder
        ]);
        
        $tracks = $query->paginate($perPage);
        $tracks->appends(request()->query());
        
        return view('genres.show', compact('genre', 'tracks', 'sortField', 'sortOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Genre $genre)
    {
        Log::info('Genre edit form accessed', ['genre_id' => $genre->id, 'name' => $genre->name]);
        return view('genres.edit', compact('genre'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Genre $genre)
    {
        Log::info('Genre update method called', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'request' => $request->except(['_token'])
        ]);

        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
            'description' => 'nullable|string',
        ]);

        try {
            $genre->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            Log::info('Genre updated successfully', ['genre_id' => $genre->id, 'name' => $genre->name]);
            
            return redirect()->route('genres.index')
                ->with('success', 'Genre updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating genre', [
                'genre_id' => $genre->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update genre: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Genre $genre)
    {
        Log::info('Genre delete method called', ['genre_id' => $genre->id, 'name' => $genre->name]);
        
        try {
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
        } catch (\Exception $e) {
            Log::error('Error deleting genre', [
                'genre_id' => $genre->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('genres.index')
                ->with('error', 'Failed to delete genre: ' . $e->getMessage());
        }
    }
}
