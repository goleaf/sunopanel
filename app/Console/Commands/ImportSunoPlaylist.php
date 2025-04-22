<?php

namespace App\Console\Commands;

use App\Models\Genre;
use App\Models\Track;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportSunoPlaylist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:suno-playlist {url? : URL of the Suno playlist API endpoint}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tracks from a Suno playlist API endpoint';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        
        if (empty($url)) {
            // If no URL provided, process all genres with genre_id
            $this->info("No URL provided. Processing playlists for all genres with genre_id...");
            
            $genres = Genre::whereNotNull('genre_id')->get();
            
            if ($genres->isEmpty()) {
                $this->error('No genres with genre_id found.');
                return 1;
            }
            
            $this->info("Found " . $genres->count() . " genres with genre_id.");
            
            $successCount = 0;
            $failedCount = 0;
            
            foreach ($genres as $genre) {
                $this->info("\nProcessing playlist for genre: {$genre->name} (ID: {$genre->genre_id})");
                
                // Construct the URL using the genre_id
                $playlistUrl = "https://studio-api.prod.suno.com/api/playlist/{$genre->genre_id}";
                
                try {
                    $this->processPlaylistUrl($playlistUrl);
                    $successCount++;
                } catch (Exception $e) {
                    $failedCount++;
                    $this->error("Failed to process playlist for genre {$genre->name}: " . $e->getMessage());
                    Log::error("Failed to process Suno playlist for genre", [
                        'genre' => $genre->name,
                        'genre_id' => $genre->genre_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $this->newLine();
            $this->info("Finished processing all genres. Successful: {$successCount}, Failed: {$failedCount}");
            
            return $failedCount > 0 ? 1 : 0;
        }
        
        // If URL is provided, process just that URL
        return $this->processPlaylistUrl($url);
    }
    
    /**
     * Process a playlist from the given URL.
     *
     * @param string $url The playlist URL to process
     * @return int Exit code (0 for success, 1 for failure)
     */
    protected function processPlaylistUrl(string $url): int
    {
        $this->info("Fetching data from: {$url}");

        try {
            // Fetch the playlist data
            $response = Http::timeout(60)->get($url);
            
            if (!$response->successful()) {
                $this->error("Failed to fetch data from URL. Status: {$response->status()}");
                return 1;
            }

            $playlistData = $response->json();
            
            if (empty($playlistData['playlist_clips'])) {
                $this->warning("No tracks found in the playlist");
                return 0;
            }

            $totalTracks = count($playlistData['playlist_clips']);
            $this->info("Found {$totalTracks} tracks in the playlist");

            $processedCount = 0;
            $failedCount = 0;

            // Process each track
            foreach ($playlistData['playlist_clips'] as $index => $clipData) {
                $clip = $clipData['clip'] ?? null;
                
                if (!$clip) {
                    $this->warning("Skipping invalid clip data at index {$index}");
                    continue;
                }

                $this->info("\nProcessing track " . ($index + 1) . "/{$totalTracks}: " . ($clip['title'] ?? 'Untitled'));
                
                try {
                    $this->processTrack($clip);
                    $processedCount++;
                    $this->info("Successfully processed track: " . ($clip['title'] ?? 'Untitled'));
                } catch (Exception $e) {
                    $failedCount++;
                    $this->error("Failed to process track: " . $e->getMessage());
                    Log::error("Failed to process Suno track", [
                        'track_title' => $clip['title'] ?? 'Untitled',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->newLine();
            $this->info("Import finished. Processed {$processedCount} tracks successfully. Failed: {$failedCount}");
            
            return $failedCount > 0 ? 1 : 0;
        } catch (Exception $e) {
            $this->error("Failed to import playlist: " . $e->getMessage());
            Log::error("Failed to import Suno playlist", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Process a single track from the Suno playlist.
     *
     * @param array $clip The clip data from the Suno API
     * @return void
     * @throws Exception
     */
    protected function processTrack(array $clip)
    {
        // Extract Suno ID
        $sunoId = $clip['id'] ?? null;
        
        // Skip if song with this Suno ID already exists
        if ($sunoId) {
            // Check if track with this Suno ID exists in the database
            $existingTrack = Track::where('suno_id', $sunoId)->first();
                
            if ($existingTrack) {
                $this->info("Track with Suno ID {$sunoId} already exists (ID: {$existingTrack->id}), skipping");
                return;
            }
        }
    
        // Extract track info
        $title = $clip['title'] ?? null;
        if (empty($title)) {
            $title = 'Untitled ' . Str::random(8);
        }

        $mp3Url = $clip['audio_url'] ?? null;
        $imageUrl = $clip['image_url'] ?? null;
        $tagsString = isset($clip['metadata']['tags']) ? $clip['metadata']['tags'] : '';
        
        // Extract metadata for genres
        $genreData = [];
        if (isset($clip['metadata']['genres']) && is_array($clip['metadata']['genres'])) {
            $genreData = $clip['metadata']['genres'];
        }

        if (empty($mp3Url)) {
            throw new Exception("MP3 URL is missing for track: {$title}");
        }

        if (empty($imageUrl)) {
            throw new Exception("Image URL is missing for track: {$title}");
        }

        // Create or update the track
        $track = Track::updateOrCreate(
            ['suno_id' => $sunoId], // Use suno_id for lookup instead of title
            [
                'title' => $title,
                'mp3_url' => $mp3Url,
                'image_url' => $imageUrl,
                'genres_string' => $tagsString,
                'status' => 'processing',
                'progress' => 0,
            ]
        );

        $this->info("Track created with ID: {$track->id}");

        // Process the track - similar to the ProcessTrack job but directly
        // Download MP3 file
        $this->comment("Downloading MP3 file...");
        $mp3Path = $this->downloadFile($mp3Url, 'mp3');
        $track->update([
            'mp3_path' => $mp3Path,
            'progress' => 33,
        ]);

        // Download image file
        $this->comment("Downloading image file...");
        $imagePath = $this->downloadFile($imageUrl, 'images');
        $track->update([
            'image_path' => $imagePath,
            'progress' => 66,
        ]);

        // Create MP4 file
        $this->comment("Creating MP4 file...");
        $mp4Path = $this->createMP4WithFFmpeg($mp3Path, $imagePath);
        $track->update([
            'mp4_path' => $mp4Path,
            'progress' => 90,
        ]);

        // Process genres
        $this->comment("Processing genres...");
        if (!empty($tagsString)) {
            $this->processGenres($track, $tagsString, $genreData);
        }

        // Update track status to completed
        $track->update([
            'status' => 'completed',
            'progress' => 100,
            'error_message' => null,
        ]);
    }

    /**
     * Download a file from URL.
     *
     * @param string $url
     * @param string $directory
     * @return string The path to the downloaded file
     * @throws Exception If download fails
     */
    protected function downloadFile(string $url, string $directory): string
    {
        try {
            // Check for Suno.ai unique ID in the URL
            $sunoIdPattern = '/https:\/\/cdn[0-9]?\.suno\.ai\/([a-f0-9-]{36})\.(?:mp3|jpeg|jpg|png)/i';
            if (preg_match($sunoIdPattern, $url, $matches)) {
                $sunoId = $matches[1];
                
                // Check for existing files with the Suno ID in the name
                $existingFiles = Storage::disk('public')->files($directory);
                foreach ($existingFiles as $existingFile) {
                    // If the file contains the Suno ID, use it instead of downloading again
                    if (Str::contains($existingFile, $sunoId)) {
                        $this->comment("File with Suno ID {$sunoId} already exists at {$existingFile}, skipping download");
                        return $existingFile;
                    }
                }
                
                // If file doesn't exist yet, include Suno ID in the filename when saving
                $extension = $this->getExtensionFromUrl($url);
                $filename = "suno_{$sunoId}." . $extension;
                $path = "{$directory}/" . $filename;
            } else {
                // If not a Suno URL, use random filename
                $extension = $this->getExtensionFromUrl($url);
                $filename = Str::random(40) . '.' . $extension;
                $path = "{$directory}/" . $filename;
            }
            
            // Download the file
            $response = Http::timeout(60)->get($url);
            
            if (!$response->successful()) {
                throw new Exception("Failed to download file from {$url}. Status: {$response->status()}");
            }
            
            // Ensure the directory exists
            Storage::disk('public')->makeDirectory($directory, 0755, true, true);
            
            Storage::disk('public')->put($path, $response->body());
            
            return $path;
        } catch (Exception $e) {
            Log::error("Failed to download file: {$e->getMessage()}", [
                'url' => $url,
            ]);
            throw new Exception("Failed to download file: {$e->getMessage()}");
        }
    }

    /**
     * Get file extension from URL.
     *
     * @param string $url
     * @return string
     */
    protected function getExtensionFromUrl(string $url): string
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        
        if (empty($extension)) {
            // Default extensions based on the directory
            if (Str::contains($url, ['mp3', 'audio'])) {
                return 'mp3';
            } elseif (Str::contains($url, ['jpg', 'jpeg', 'png', 'image'])) {
                return 'jpg';
            }
            return 'dat'; // Default if no extension can be determined
        }
        
        return $extension;
    }

    /**
     * Create an MP4 file from audio and image using FFmpeg.
     *
     * @param string $mp3Path
     * @param string $imagePath
     * @return string The path to the created MP4 file
     * @throws Exception If creation fails
     */
    protected function createMP4WithFFmpeg(string $mp3Path, string $imagePath): string
    {
        try {
            // Get full paths for files
            $mp3FullPath = Storage::disk('public')->path($mp3Path);
            $imageFullPath = Storage::disk('public')->path($imagePath);
            
            // Check if files exist
            if (!file_exists($mp3FullPath)) {
                throw new Exception("MP3 file not found at {$mp3FullPath}");
            }
            
            if (!file_exists($imageFullPath)) {
                throw new Exception("Image file not found at {$imageFullPath}");
            }
            
            // Create unique output filename
            $outputFilename = Str::random(40) . '.mp4';
            $outputDirectory = 'videos';
            $outputPath = "{$outputDirectory}/{$outputFilename}";
            $outputFullPath = Storage::disk('public')->path($outputPath);
            
            // Ensure videos directory exists
            Storage::disk('public')->makeDirectory($outputDirectory, 0755, true, true);
            
            // Common scale filter to limit max dimension to 700px while maintaining aspect ratio
            $scaleFilter = "scale=w='min(700,iw)':h='min(700,ih)':force_original_aspect_ratio=decrease";
            
            // Prepare command arguments
            $escapedImagePath = escapeshellarg($imageFullPath);
            $escapedMp3Path = escapeshellarg($mp3FullPath);
            $escapedOutputPath = escapeshellarg($outputFullPath);
            
            // Command with libx264
            $complexScaleFilter = "{$scaleFilter},scale=trunc(iw/2)*2:trunc(ih/2)*2";
            $command = "ffmpeg -y -loop 1 -framerate 1 -i {$escapedImagePath} -i {$escapedMp3Path} -c:v libx264 -tune stillimage -pix_fmt yuv420p -c:a aac -b:a 192k -shortest -vf \"{$complexScaleFilter}\" {$escapedOutputPath} 2>&1";
            
            $this->comment("Running FFmpeg command...");
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($outputFullPath)) {
                // Fallback to simpler command if first attempt fails
                $command = "ffmpeg -y -loop 1 -framerate 1 -i {$escapedImagePath} -i {$escapedMp3Path} -c:v mjpeg -q:v 3 -c:a copy -vf \"{$scaleFilter}\" -shortest {$escapedOutputPath} 2>&1";
                
                $this->comment("First attempt failed, trying alternative FFmpeg command...");
                
                exec($command, $output, $returnCode);
                
                if ($returnCode !== 0 || !file_exists($outputFullPath)) {
                    throw new Exception("Failed to create MP4 file: " . implode("\n", $output));
                }
            }
            
            // Return the relative path for storage
            return $outputPath;
        } catch (Exception $e) {
            Log::error("Failed to create MP4: {$e->getMessage()}", [
                'mp3_path' => $mp3Path,
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to create MP4: {$e->getMessage()}");
        }
    }

    /**
     * Process genres string and attach them to the track.
     *
     * @param Track $track
     * @param string $genresString
     * @param array $genreData
     * @return void
     */
    protected function processGenres(Track $track, string $genresString, array $genreData = []): void
    {
        if (empty($genresString)) {
            return;
        }
        
        $this->info("Processing genres: '{$genresString}'");
        
        // Split by comma and remove duplicates
        $genreNames = array_unique(array_map('trim', explode(',', $genresString)));
        $genreIds = [];
        
        foreach ($genreNames as $genreName) {
            if (empty($genreName)) {
                continue;
            }
            
            try {
                // Normalize genre name for consistency
                $normalizedName = ucwords(strtolower($genreName));
                $slug = Str::slug($normalizedName);
                
                // Look for genre_id in the genre data
                $genre_id = null;
                foreach ($genreData as $genreItem) {
                    if (isset($genreItem['name']) && strtolower($genreItem['name']) === strtolower($genreName)) {
                        $genre_id = $genreItem['id'] ?? null;
                        break;
                    }
                }
                
                // First try to find existing genre by slug
                $genre = Genre::firstWhere('slug', $slug);
                
                // If not found, create a new one
                if (!$genre) {
                    // Create the genre
                    $genre = Genre::create([
                        'name' => $normalizedName,
                        'slug' => $slug,
                        'genre_id' => $genre_id,
                    ]);
                    $this->comment("Created new genre: {$normalizedName}" . ($genre_id ? " with ID: {$genre_id}" : ""));
                } else {
                    // Update the genre_id if we have one from metadata and the existing genre doesn't have it
                    if ($genre_id && empty($genre->genre_id)) {
                        $genre->update(['genre_id' => $genre_id]);
                        $this->comment("Updated existing genre: {$normalizedName} with ID: {$genre_id}");
                    } else {
                        $this->comment("Found existing genre: {$normalizedName}" . ($genre->genre_id ? " with ID: {$genre->genre_id}" : ""));
                    }
                }
                
                $genreIds[] = $genre->id;
            } catch (Exception $e) {
                // Log the error but continue processing other genres
                $this->warning("Failed to process genre '{$genreName}': {$e->getMessage()}");
            }
        }
        
        if (!empty($genreIds)) {
            // Sync genres with the track
            $track->genres()->sync($genreIds);
            
            // Verify genres were attached
            $track->refresh();
            $attachedGenres = $track->genres->pluck('name')->toArray();
            $this->info("Attached genres: " . implode(', ', $attachedGenres));
        } else {
            $this->warning("No valid genres to attach");
        }
    }
} 