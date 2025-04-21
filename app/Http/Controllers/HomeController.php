<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTrack;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Show the home page with form for adding tracks.
     */
    public function index()
    {
        return view('home.index');
    }
    
    /**
     * Process the submitted batch of tracks.
     */
    public function process(Request $request)
    {
        $request->validate([
            'tracks_data' => 'required|string'
        ]);
        
        $lines = explode("\n", $request->tracks_data);
        $processedCount = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                $parts = explode('|', $line);
                if (count($parts) !== 4) {
                    continue;
                }
                
                [$title, $mp3Url, $imageUrl, $genresString] = $parts;
                
                // Create track
                $track = Track::create([
                    'title' => str_replace('.mp3', '', $title),
                    'mp3_url' => $mp3Url,
                    'image_url' => $imageUrl,
                    'status' => 'pending',
                    'progress' => 0
                ]);
                
                // Process genres
                $genreNames = explode(',', $genresString);
                $genres = [];
                
                foreach ($genreNames as $genreName) {
                    $genreName = trim($genreName);
                    if (empty($genreName)) {
                        continue;
                    }
                    
                    $slug = Str::slug($genreName);
                    $genre = Genre::firstOrCreate(
                        ['slug' => $slug],
                        ['name' => $genreName]
                    );
                    
                    $genres[] = $genre->id;
                }
                
                $track->genres()->sync($genres);
                
                // Dispatch job to process the track
                ProcessTrack::dispatch($track);
                
                $processedCount++;
            }
            
            DB::commit();
            
            return redirect()->route('home.index')
                ->with('success', "{$processedCount} tracks have been queued for processing.");
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('home.index')
                ->with('error', 'An error occurred while processing the tracks: ' . $e->getMessage());
        }
    }
}
