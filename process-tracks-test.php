<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Track;
use App\Jobs\ProcessTrack;
use Illuminate\Foundation\Application;

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Track list from the provided data
$tracks = [
    ['title' => 'Fleeting Love (儚い愛)', 'mp3_url' => 'https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3', 'image_url' => 'https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg', 'genres' => ['City pop', '80s']],
    ['title' => 'Palakpakan', 'mp3_url' => 'https://cdn1.suno.ai/9a00dc20-9640-4150-9804-d8a179ce860c.mp3', 'image_url' => 'https://cdn2.suno.ai/image_9a00dc20-9640-4150-9804-d8a179ce860c.jpeg', 'genres' => ['city pop']],
    ['title' => 'ジャカジャカ', 'mp3_url' => 'https://cdn1.suno.ai/837cd038-c104-405b-b1d5-bafa924a277f.mp3', 'image_url' => 'https://cdn2.suno.ai/image_837cd038-c104-405b-b1d5-bafa924a277f.jpeg', 'genres' => ['city pop']],
    ['title' => 'nihongo jouzu', 'mp3_url' => 'https://cdn1.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410.mp3', 'image_url' => 'https://cdn2.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410_e90ebc18.jpeg', 'genres' => ['city pop']],
    ['title' => '无言的告别', 'mp3_url' => 'https://cdn1.suno.ai/86c03eaa-facb-487c-96d5-015a0d3fcc72.mp3', 'image_url' => 'https://cdn2.suno.ai/image_463417b7-1282-4083-a681-c11848872ba1.jpeg', 'genres' => ['lofi', 'City pop', 'R&B']],
];

// Process each track
foreach ($tracks as $trackData) {
    try {
        echo "Processing track: {$trackData['title']}\n";
        
        // Find existing track or create a new one
        $track = Track::firstOrNew(['title' => $trackData['title']]);
        $track->mp3_url = $trackData['mp3_url'];
        $track->image_url = $trackData['image_url'];
        $track->setProgress(0);
        $track->setStatus('queued');
        $track->save();
        
        // Create or update track genres
        if (!empty($trackData['genres'])) {
            $genreIds = [];
            foreach ($trackData['genres'] as $genreName) {
                $genre = \App\Models\Genre::firstOrCreate(['name' => trim($genreName)]);
                $genreIds[] = $genre->id;
            }
            
            // Sync genres with the track
            $track->genres()->sync($genreIds);
        }
        
        // Process the track immediately (not queued)
        echo "Starting immediate processing of track ID {$track->id}\n";
        $job = new ProcessTrack($track);
        $job->handle();
        
        echo "Completed processing track: {$trackData['title']}\n";
    } catch (\Exception $e) {
        echo "Error processing track {$trackData['title']}: {$e->getMessage()}\n";
        echo $e->getTraceAsString() . "\n";
    }
}

echo "All tracks processed\n"; 