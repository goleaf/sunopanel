<?php

namespace App\Http\Controllers;

use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackController extends Controller
{
    /**
     * Display a listing of the tracks.
     */
    public function index(): View
    {
        $tracks = Track::orderBy('created_at', 'desc')->paginate(15);
        return view('tracks.index', compact('tracks'));
    }

    /**
     * Display the specified track.
     */
    public function show(Track $track): View
    {
        return view('tracks.show', compact('track'));
    }

    /**
     * Remove the specified track from storage.
     */
    public function destroy(Track $track)
    {
        // Delete associated files
        if ($track->mp3_path) {
            \Storage::disk('public')->delete($track->mp3_path);
        }
        
        if ($track->image_path) {
            \Storage::disk('public')->delete($track->image_path);
        }
        
        if ($track->mp4_path) {
            \Storage::disk('public')->delete($track->mp4_path);
        }
        
        // Delete record
        $track->genres()->detach();
        $track->delete();
        
        return redirect()->route('tracks.index')
            ->with('success', "Track '{$track->title}' has been deleted.");
    }

    /**
     * Get the current status of a track (for AJAX requests).
     */
    public function status(Track $track): JsonResponse
    {
        return response()->json([
            'id' => $track->id,
            'status' => $track->status,
            'progress' => $track->progress,
            'error_message' => $track->error_message,
        ]);
    }
}
