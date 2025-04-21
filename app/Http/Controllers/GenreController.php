<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    /**
     * Display a listing of the genres.
     */
    public function index()
    {
        $genres = Genre::withCount('tracks')->orderBy('name')->paginate(20);
        return view('genres.index', compact('genres'));
    }

    /**
     * Display the specified genre.
     */
    public function show(Genre $genre)
    {
        $tracks = $genre->tracks()->paginate(10);
        return view('genres.show', compact('genre', 'tracks'));
    }
}
