<?php

// Load Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\ProcessTrack;
use App\Models\Track;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

echo "Starting track processing test...\n";

// The tracks data to process
$tracks = [
    "Fleeting Love (儚い愛).mp3|https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3|https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg|City pop,80s",
    "Palakpakan.mp3|https://cdn1.suno.ai/9a00dc20-9640-4150-9804-d8a179ce860c.mp3|https://cdn2.suno.ai/image_9a00dc20-9640-4150-9804-d8a179ce860c.jpeg|city pop",
    "ジャカジャカ.mp3|https://cdn1.suno.ai/837cd038-c104-405b-b1d5-bafa924a277f.mp3|https://cdn2.suno.ai/image_837cd038-c104-405b-b1d5-bafa924a277f.jpeg|city pop",
    "nihongo jouzu.mp3|https://cdn1.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410.mp3|https://cdn2.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410_e90ebc18.jpeg|city pop",
    "无言的告别.mp3|https://cdn1.suno.ai/86c03eaa-facb-487c-96d5-015a0d3fcc72.mp3|https://cdn2.suno.ai/image_463417b7-1282-4083-a681-c11848872ba1.jpeg|lofi,City pop,R&B",
    "City of Sound.mp3|https://cdn1.suno.ai/52f2608e-d8fe-44e7-ab9a-5d6778dea12e.mp3|https://cdn2.suno.ai/52f2608e-d8fe-44e7-ab9a-5d6778dea12e_8f0f8ca2.jpeg|City pop synthwave vaporwave",
    "Neon Nights.mp3|https://cdn1.suno.ai/bfd2531b-f8a5-432c-9bf8-8d5c2cac3a26.mp3|https://cdn2.suno.ai/bfd2531b-f8a5-432c-9bf8-8d5c2cac3a26_4ba38742.jpeg|city pop synthwave vaporwave",
    "Humor sentido 3.mp3|https://cdn1.suno.ai/f474c102-46df-47a9-9226-4f6bb54a3f50.mp3|https://cdn2.suno.ai/image_f474c102-46df-47a9-9226-4f6bb54a3f50.jpeg|City pop psybient",
    "City Lights.mp3|https://cdn1.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880.mp3|https://cdn2.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880_ffc2643f.jpeg|City pop",
    "Loop the loop.mp3|https://cdn1.suno.ai/25ad5bcc-266b-4777-8d5f-1fa48a3b99af.mp3|https://cdn2.suno.ai/image_8299a9b0-77e6-42c7-8cbe-a41494ed30df.jpeg|CITY POP",
    "Midnight Mirage.mp3|https://cdn1.suno.ai/4aaf14ca-ea58-4e97-87e3-b19aca31595a.mp3|https://cdn2.suno.ai/image_4aaf14ca-ea58-4e97-87e3-b19aca31595a.jpeg|acid rock city pop",
    "キラメキ・ステップ.mp3|https://cdn1.suno.ai/a488d07e-e8c3-4335-aa18-f384cb133275.mp3|https://cdn2.suno.ai/a488d07e-e8c3-4335-aa18-f384cb133275_ba783930.jpeg|80s City POP",
    "Summer in Tokyo (ALT).mp3|https://cdn1.suno.ai/7a41ee58-c865-4ad0-83e3-817d36188eea.mp3|https://cdn2.suno.ai/image_7d3092ac-8c3a-4f5d-bddf-c9e5ac8dcd5f.jpeg|New age funk,city pop,brass band",
    "Gigolos for Laundry.mp3|https://cdn1.suno.ai/328e1f08-32ab-4929-8c1c-38f94b71f1e1.mp3|https://cdn2.suno.ai/328e1f08-32ab-4929-8c1c-38f94b71f1e1_56694827.jpeg|disco,japanese Nostalgic city-pop",
    "I've Never Loved Like This.mp3|https://cdn1.suno.ai/07a1add8-fbf9-48e9-b1ad-6b6a4822493e.mp3|https://cdn2.suno.ai/07a1add8-fbf9-48e9-b1ad-6b6a4822493e_478a2e50.jpeg|AOR,city-pop",
    "Radio Waves.mp3|https://cdn1.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895.mp3|https://cdn2.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895_cbda2f8e.jpeg|City POP",
    "CÂY ƠI!.mp3|https://cdn1.suno.ai/71f013a4-0903-4991-a990-f1051a59a193.mp3|https://cdn2.suno.ai/image_71f013a4-0903-4991-a990-f1051a59a193.jpeg|city pop",
    "ElectricKinetic  (RemixRearrange).mp3|https://cdn1.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c.mp3|https://cdn2.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c_2c38cfb6.jpeg|city pop,dance,pop,clean production",
    "My heart is break again.mp3|https://cdn1.suno.ai/158326b3-e0bc-4939-bd30-26a49ef86d55.mp3|https://cdn2.suno.ai/image_158326b3-e0bc-4939-bd30-26a49ef86d55.jpeg|lo-fi jazz city pop"
];

$createdTracks = [];

// Process each track
foreach ($tracks as $line) {
    $line = trim($line);
    
    // Skip empty lines
    if (empty($line)) {
        continue;
    }
    
    try {
        // Parse the line
        $parts = explode('|', $line);
        
        if (count($parts) < 3) {
            echo "Invalid track format: {$line}\n";
            continue;
        }
        
        $fileName = trim($parts[0]);
        $mp3Url = trim($parts[1]);
        $imageUrl = trim($parts[2]);
        $genresString = isset($parts[3]) ? trim($parts[3]) : '';
        
        // Clean up title (remove .mp3 extension)
        $title = str_replace('.mp3', '', $fileName);
        
        // Create/update the track
        $track = Track::updateOrCreate(
            ['title' => $title],
            [
                'mp3_url' => $mp3Url,
                'image_url' => $imageUrl,
                'genres_string' => $genresString,
                'status' => 'pending',
                'progress' => 0,
                'slug' => Str::slug($title),
            ]
        );
        
        echo "Added track: {$title}\n";
        $createdTracks[] = $track;
        
    } catch (Exception $e) {
        echo "Error processing track: {$e->getMessage()}\n";
        Log::error("Failed to process track: {$e->getMessage()}", [
            'line' => $line,
            'error' => $e->getMessage(),
        ]);
    }
}

echo "\n" . count($createdTracks) . " tracks have been added to the database.\n";
echo "Now dispatching jobs to process tracks...\n\n";

// Process each track immediately (not using the queue)
$count = 0;
foreach ($createdTracks as $track) {
    try {
        echo "Processing track: {$track->title}...\n";
        
        // Create new ProcessTrack job and execute it
        $job = new ProcessTrack($track);
        $job->handle();
        
        echo "Completed processing: {$track->title}\n";
        $count++;
    } catch (Exception $e) {
        echo "Error processing {$track->title}: {$e->getMessage()}\n";
    }
    
    echo "------------------------------------\n";
}

echo "\nProcessed {$count} out of " . count($createdTracks) . " tracks.\n";
echo "Check the database and storage for the results.\n"; 