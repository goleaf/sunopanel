<?php

namespace App\Http\Controllers;

use App\Models\Track;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * Display a listing of the tracks.
     */
    public function index()
    {
        $tracks = Track::with('genres')->latest()->paginate(10);
        return view('tracks.index', compact('tracks'));
    }

    /**
     * Display the specified track.
     */
    public function show(Track $track)
    {
        $track->load('genres');
        return view('tracks.show', compact('track'));
    }

    /**
     * Remove the specified track from storage.
     */
    public function destroy(Track $track)
    {
        $track->delete();
        return redirect()->route('tracks.index')
            ->with('success', 'Track deleted successfully');
    }
    
    /**
     * Get the track processing status.
     */
    public function status(Track $track)
    {
        return response()->json([
            'status' => $track->status,
            'progress' => $track->progress,
            'error' => $track->error_message,
        ]);
    }
}
