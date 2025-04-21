<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * Display a listing of the genres.
     */
    public function index(): View
    {
        $genres = Genre::withCount('tracks')->orderBy('name')->paginate(15);
        
        return view('genres.index', compact('genres'));
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
        ]);

        Genre::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('genres.index')->with('success', 'Genre created successfully');
    }

    /**
     * Display the specified genre.
     */
    public function show(Genre $genre): View
    {
        $tracks = $genre->tracks()->paginate(15);
        
        return view('genres.show', compact('genre', 'tracks'));
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
        ]);

        $genre->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
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
