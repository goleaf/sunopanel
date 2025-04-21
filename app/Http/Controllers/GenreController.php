<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreDeleteRequest;
use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use App\Models\Genre;
use Illuminate\Support\Facades\Log;

final class GenreController extends Controller
{
    /**
     * Store a newly created genre.
     */
    public function store(GenreStoreRequest $request)
    {
        $validated = $request->validated();
        
        try {
            $genre = Genre::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);
            
            Log::info('Genre created', [
                'genre_id' => $genre->id,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('genres.index')
                ->with('success', 'Genre created successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating genre', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->back()
                ->with('error', 'Error creating genre: ' . $e->getMessage());
        }
    }
    
    /**
     * Update the specified genre.
     */
    public function update(GenreUpdateRequest $request, Genre $genre)
    {
        $validated = $request->validated();
        
        try {
            $genre->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $genre->description,
            ]);
            
            Log::info('Genre updated', [
                'genre_id' => $genre->id,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('genres.index')
                ->with('success', 'Genre updated successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error updating genre', [
                'genre_id' => $genre->id,
                'user_id' => auth()->id() ?? 'guest',
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error updating genre: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove the specified genre.
     */
    public function destroy(Genre $genre, GenreDeleteRequest $request)
    {
        try {
            $genre->delete();
            
            Log::info('Genre deleted', [
                'genre_id' => $genre->id,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return redirect()->route('genres.index')
                ->with('success', 'Genre deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting genre', [
                'genre_id' => $genre->id,
                'user_id' => auth()->id() ?? 'guest',
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting genre: ' . $e->getMessage());
        }
    }
} 