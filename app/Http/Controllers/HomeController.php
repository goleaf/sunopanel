<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTrack;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the add tracks form.
     */
    public function index(): View
    {
        return view('home.index');
    }

    /**
     * Process the submitted tracks.
     */
    public function process(Request $request)
    {
        $request->validate([
            'tracks_input' => 'required|string|min:10',
        ]);

        $tracksText = $request->input('tracks_input');
        $parsedTracks = Track::parseFromText($tracksText);
        $createdTracks = [];

        foreach ($parsedTracks as $trackData) {
            try {
                // Create/update the track
                $track = Track::updateOrCreate(
                    ['title' => $trackData['title']],
                    $trackData
                );
                
                // Dispatch the processing job
                ProcessTrack::dispatch($track);
                
                $createdTracks[] = $track;
                
                Log::info("Track queued for processing: {$track->title}");
            } catch (\Exception $e) {
                Log::error("Failed to process track: {$e->getMessage()}", [
                    'trackData' => $trackData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $count = count($createdTracks);
        
        return redirect()->route('tracks.index')
            ->with('success', "{$count} tracks have been queued for processing");
    }
}
