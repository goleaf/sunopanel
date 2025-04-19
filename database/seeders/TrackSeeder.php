<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\Track;
use App\Models\Genre;
use App\Models\Playlist;

class TrackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::info('Starting Track Seeder');

        try {
            // Sample track data
            $tracksData = [
                [
                    'title' => 'Gimme yo Milk',
                    'audio_url' => 'https://cdn1.suno.ai/d339f150-da9d-47e7-a241-86f9504c4298.mp3',
                    'image_url' => 'https://cdn2.suno.ai/d339f150-da9d-47e7-a241-86f9504c4298_8c36669a.jpeg',
                    'genres' => ['Hypnotic trance', 'Bubblegum bass']
                ],
                [
                    'title' => 'Neon Threads',
                    'audio_url' => 'https://cdn1.suno.ai/c20d86aa-3936-4f0d-86c4-6e52f802aaa6.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_c20d86aa-3936-4f0d-86c4-6e52f802aaa6.jpeg',
                    'genres' => ['Bubblegum bass']
                ],
                [
                    'title' => 'Whispers from the Starlight',
                    'audio_url' => 'https://cdn1.suno.ai/6fd235bf-5ef5-477e-8872-925aa9fb5c81.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_6fd235bf-5ef5-477e-8872-925aa9fb5c81.jpeg',
                    'genres' => ['Bubblegum bass', 'Symphonic metal']
                ],
                [
                    'title' => 'Echoes of Solitude',
                    'audio_url' => 'https://cdn1.suno.ai/a30c4223-b2f0-4edb-a346-9f0d462b0d73.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_a30c4223-b2f0-4edb-a346-9f0d462b0d73.jpeg',
                    'genres' => ['Chillwave', 'Bubblegum bass']
                ],
                [
                    'title' => 'Who Stole my Fans!',
                    'audio_url' => 'https://cdn1.suno.ai/0243cd63-807b-4d4f-a110-42afb2c4233d.mp3',
                    'image_url' => 'https://cdn2.suno.ai/0243cd63-807b-4d4f-a110-42afb2c4233d_30a25352.jpeg',
                    'genres' => ['Tech-house', 'Electrohouse', 'Bubblegum bass']
                ],
                [
                    'title' => 'Bananicorn',
                    'audio_url' => 'https://cdn1.suno.ai/e598c515-5c35-404e-b2c7-b6990bfc6773.mp3',
                    'image_url' => 'https://cdn2.suno.ai/e598c515-5c35-404e-b2c7-b6990bfc6773_41f53355.jpeg',
                    'genres' => ['Rave', 'Bubblegum bass', 'Afrobeat', 'Urban-Pop']
                ],
                [
                    'title' => 'Technicolor Dreamscape',
                    'audio_url' => 'https://cdn1.suno.ai/854689aa-4894-454a-9f77-194f1b0771ee.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_854689aa-4894-454a-9f77-194f1b0771ee.jpeg',
                    'genres' => ['Bubblegum bass', 'Grime', 'Glitchy', 'Sweet', 'Edgy beats']
                ],
                [
                    'title' => 'Hangxiety',
                    'audio_url' => 'https://cdn1.suno.ai/e757aa19-5c33-4945-aaa1-1519b7890f0b.mp3',
                    'image_url' => 'https://cdn2.suno.ai/e757aa19-5c33-4945-aaa1-1519b7890f0b_f4ffb4e2.jpeg',
                    'genres' => ['Electrohouse', 'EDM', 'Bubblegum bass', 'Pop', 'Folk']
                ],
                [
                    'title' => 'Bluegrass Bubblegum Heartache',
                    'audio_url' => 'https://cdn1.suno.ai/a11656f2-daa7-4233-8b0c-7a55134e5e34.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_a11656f2-daa7-4233-8b0c-7a55134e5e34.jpeg',
                    'genres' => ['Bluegrass', 'Bubblegum bass']
                ],
                [
                    'title' => 'Love Yourself Wonderland',
                    'audio_url' => 'https://cdn1.suno.ai/79b60772-ebad-4dae-ad0c-bcdc02d6e614.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_79b60772-ebad-4dae-ad0c-bcdc02d6e614.jpeg',
                    'genres' => ['Lo-fi', 'Bubblegum bass']
                ],
                [
                    'title' => 'KALACHNIVOK ft. Hiatus1984',
                    'audio_url' => 'https://cdn1.suno.ai/ab770f0e-5242-488a-8ec4-9c013ea158da.mp3',
                    'image_url' => 'https://cdn2.suno.ai/ab770f0e-5242-488a-8ec4-9c013ea158da_97d7851b.jpeg',
                    'genres' => ['Frenchore', 'Dubcore', 'Wobble synth', 'Bubblegum bass']
                ],
                [
                    'title' => 'UPDATE! 3D',
                    'audio_url' => 'https://cdn1.suno.ai/ee4f4c39-f346-409b-b14e-24e4a4542cb9.mp3',
                    'image_url' => 'https://cdn2.suno.ai/ee4f4c39-f346-409b-b14e-24e4a4542cb9_ccd47a63.jpeg',
                    'genres' => ['Ringtone', 'Ambient pop', 'Bubblegum bass']
                ],
                [
                    'title' => 'three яйца',
                    'audio_url' => 'https://cdn1.suno.ai/5ec8adae-aea3-4557-8ade-49039558826f.mp3',
                    'image_url' => 'https://cdn2.suno.ai/5ec8adae-aea3-4557-8ade-49039558826f_648bf1a1.jpeg',
                    'genres' => ['Bubblegum bass', 'Trap', 'Drift']
                ],
                [
                    'title' => '資本主義✦吾黨所宗',
                    'audio_url' => 'https://cdn1.suno.ai/5220f68c-6623-43db-a4c8-8071a471ab5e.mp3',
                    'image_url' => 'https://cdn2.suno.ai/5220f68c-6623-43db-a4c8-8071a471ab5e_a151c500.jpeg',
                    'genres' => ['K-pop', 'Girl group', 'Bubblegum bass']
                ],
                [
                    'title' => '(#) Burrito-Brunch-Thursday',
                    'audio_url' => 'https://cdn1.suno.ai/c1642c38-087d-445d-9b11-3cf45591aa43.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_c1642c38-087d-445d-9b11-3cf45591aa43.jpeg',
                    'genres' => ['Bubblegum bass', 'Symphonic metal', 'Southern rock', 'Ska']
                ],
                [
                    'title' => 'Echoes from the Cosmos',
                    'audio_url' => 'https://cdn1.suno.ai/4aa6178d-fe2e-441d-b208-e6602bbf04c5.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_4aa6178d-fe2e-441d-b208-e6602bbf04c5.jpeg',
                    'genres' => ['Bubblegum bass', 'Symphonic metal']
                ],
                [
                    'title' => 'Quando o prato é muito disputado',
                    'audio_url' => 'https://cdn1.suno.ai/a1d7484b-d981-4d59-a9e1-5af07a177d6a.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_a1d7484b-d981-4d59-a9e1-5af07a177d6a.jpeg',
                    'genres' => ['Chillwave', 'Bubblegum bass']
                ],
                [
                    'title' => 'Supply, как сопли Ха!',
                    'audio_url' => 'https://cdn1.suno.ai/7ac7b9c8-c705-4174-843d-424e1deb1c25.mp3',
                    'image_url' => 'https://cdn2.suno.ai/image_7ac7b9c8-c705-4174-843d-424e1deb1c25.jpeg',
                    'genres' => ['Bubblegum bass', 'Symphonic metal']
                ],
                [
                    'title' => 'ADDICTED',
                    'audio_url' => 'https://cdn1.suno.ai/5706fb67-8c53-46ce-b735-e4a2ea501297.mp3',
                    'image_url' => 'https://cdn2.suno.ai/5706fb67-8c53-46ce-b735-e4a2ea501297_ae0db680.jpeg',
                    'genres' => ['Hardstyle', 'Bubblegum bass', 'Major key', 'Acid house', 'Glitch witch', 'Artcore']
                ],
                [
                    'title' => 'Electroshock!',
                    'audio_url' => 'https://cdn1.suno.ai/97b094aa-29f9-4044-9131-a713dda6f37a.mp3',
                    'image_url' => 'https://cdn2.suno.ai/97b094aa-29f9-4044-9131-a713dda6f37a_0e4d9ba2.jpeg',
                    'genres' => ['Drum and bass', 'Dubstep', 'Wobblestep', 'Bubblegum bass']
                ],
            ];

            // Create genres
            $allGenres = collect();
            
            Log::info('Processing genres...');
            foreach ($tracksData as $trackData) {
                foreach ($trackData['genres'] as $genreName) {
                    // Handle bubblegum bass special case
                    if (strcasecmp(trim($genreName), 'bubblegum bass') === 0 || strcasecmp(trim($genreName), 'bubblegum-bass') === 0) {
                        $formattedName = 'Bubblegum bass';
                    } else {
                        // Format the genre name with only the first letter uppercase
                        $formattedName = ucfirst(strtolower(trim($genreName)));
                    }
                    
                    if (!$allGenres->has($formattedName)) {
                        $genre = Genre::firstOrCreate(['name' => $formattedName]);
                        $allGenres->put($formattedName, $genre);
                        Log::info("Genre created or found", [
                            'id' => $genre->id, 
                            'name' => $formattedName, 
                            'slug' => $genre->slug
                        ]);
                    }
                }
            }

            Log::info('Creating tracks and associating genres...');
            // Create tracks and associate genres
            foreach ($tracksData as $trackData) {
                $track = Track::firstOrCreate(
                    ['title' => $trackData['title']],
                    [
                        'audio_url' => $trackData['audio_url'],
                        'image_url' => $trackData['image_url'],
                        'unique_id' => Track::generateUniqueId($trackData['title']),
                        'duration' => '3:00'
                    ]
                );
                
                Log::info("Track created or found", [
                    'id' => $track->id,
                    'title' => $track->title
                ]);
                
                // Attach genres
                $genreIds = [];
                foreach ($trackData['genres'] as $genreName) {
                    // Handle bubblegum bass special case
                    if (strcasecmp(trim($genreName), 'bubblegum bass') === 0 || strcasecmp(trim($genreName), 'bubblegum-bass') === 0) {
                        $formattedName = 'Bubblegum bass';
                    } else {
                        $formattedName = ucfirst(strtolower(trim($genreName)));
                    }
                    
                    $genre = $allGenres->get($formattedName);
                    if ($genre) {
                        $genreIds[] = $genre->id;
                    }
                }
                
                $track->genres()->sync($genreIds);
                Log::info("Genres attached to track", [
                    'track_id' => $track->id,
                    'track_title' => $track->title, 
                    'genre_count' => count($genreIds),
                    'genres' => $track->genres()->pluck('name')->toArray()
                ]);
            }
            
            Log::info('Creating sample playlists...');
            // Create sample playlists
            $playlists = [
                [
                    'name' => 'Bubblegum Bass Favorites',
                    'description' => 'A collection of the best bubblegum bass tracks',
                    'genre' => 'Bubblegum bass'
                ],
                [
                    'name' => 'Chillwave Mix',
                    'description' => 'Relaxing chillwave tracks for your downtime',
                    'genre' => 'Chillwave'
                ],
                [
                    'name' => 'Metal Madness',
                    'description' => 'Heavy symphonic metal tracks',
                    'genre' => 'Symphonic metal'
                ],
                [
                    'name' => 'Electronic Fusion',
                    'description' => 'A mix of various electronic music styles',
                    'genres' => ['Electrohouse', 'EDM', 'Tech-house']
                ],
                [
                    'name' => 'Global Vibes',
                    'description' => 'Music with international influences',
                    'genres' => ['K-pop', 'Afrobeat', 'Urban-pop']
                ],
                [
                    'name' => 'Lo-Fi Study Session',
                    'description' => 'Perfect background music for studying or working',
                    'genre' => 'Lo-fi'
                ]
            ];
            
            foreach ($playlists as $playlistData) {
                $playlist = Playlist::firstOrCreate(
                    ['name' => $playlistData['name']],
                    ['description' => $playlistData['description']]
                );
                
                Log::info("Playlist created or found", [
                    'id' => $playlist->id,
                    'name' => $playlist->name
                ]);
                
                // Find tracks by genre and attach to playlist
                if (isset($playlistData['genre'])) {
                    $genreName = $playlistData['genre'];
                    // Handle bubblegum bass special case
                    if (strcasecmp(trim($genreName), 'bubblegum bass') === 0 || strcasecmp(trim($genreName), 'bubblegum-bass') === 0) {
                        $genreName = 'Bubblegum bass';
                    } else {
                        $genreName = ucfirst(strtolower(trim($genreName)));
                    }
                    
                    $genre = $allGenres->get($genreName);
                    if ($genre) {
                        $tracks = $genre->tracks;
                        $position = 1;
                        foreach ($tracks as $track) {
                            $playlist->tracks()->attach($track->id, ['position' => $position]);
                            $position++;
                        }
                        
                        Log::info("Tracks attached to playlist from genre", [
                            'playlist_id' => $playlist->id,
                            'playlist_name' => $playlist->name,
                            'genre' => $genreName,
                            'track_count' => $tracks->count()
                        ]);
                    }
                } elseif (isset($playlistData['genres']) && is_array($playlistData['genres'])) {
                    $position = 1;
                    $trackCount = 0;
                    
                    foreach ($playlistData['genres'] as $genreName) {
                        // Handle bubblegum bass special case
                        if (strcasecmp(trim($genreName), 'bubblegum bass') === 0 || strcasecmp(trim($genreName), 'bubblegum-bass') === 0) {
                            $formattedName = 'Bubblegum bass';
                        } else {
                            $formattedName = ucfirst(strtolower(trim($genreName)));
                        }
                        
                        $genre = $allGenres->get($formattedName);
                        if ($genre) {
                            $tracks = $genre->tracks;
                            foreach ($tracks as $track) {
                                // Check if track is already in playlist
                                if (!$playlist->tracks()->where('track_id', $track->id)->exists()) {
                                    $playlist->tracks()->attach($track->id, ['position' => $position]);
                                    $position++;
                                    $trackCount++;
                                }
                            }
                        }
                    }
                    
                    Log::info("Tracks attached to playlist from multiple genres", [
                        'playlist_id' => $playlist->id,
                        'playlist_name' => $playlist->name,
                        'genres' => $playlistData['genres'],
                        'track_count' => $trackCount
                    ]);
                }
            }
            
            Log::info('TrackSeeder completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in TrackSeeder: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Process raw track data from text format (used in tests)
     * Format: title|audio_url|image_url|genres
     */
    public function processBulkData($bulkText)
    {
        Log::info('Processing bulk track data');
        $lines = explode(PHP_EOL, $bulkText);
        $processedCount = 0;
        $errors = [];
        
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            try {
                $parts = explode('|', $line);
                if (count($parts) < 4) {
                    $errors[] = "Line " . ($index + 1) . ": Invalid format - expected at least 4 parts separated by |";
                    continue;
                }
                
                $title = trim($parts[0]);
                $audioUrl = trim($parts[1]);
                $imageUrl = trim($parts[2]);
                $genresRaw = trim($parts[3]);
                $duration = isset($parts[4]) ? trim($parts[4]) : '3:00';
                
                // Skip if track with this title already exists
                if (Track::where('title', $title)->exists()) {
                    $errors[] = "Line " . ($index + 1) . ": Track '{$title}' already exists";
                    continue;
                }
                
                // Create track
                $track = Track::create([
                    'title' => $title,
                    'audio_url' => $audioUrl,
                    'image_url' => $imageUrl,
                    'unique_id' => Track::generateUniqueId($title),
                    'duration' => $duration
                ]);
                
                // Sync genres
                $track->syncGenres($genresRaw);
                
                $processedCount++;
                Log::info('Bulk track created', [
                    'index' => $index,
                    'title' => $title,
                    'track_id' => $track->id,
                    'genres' => $genresRaw
                ]);
            } catch (\Exception $e) {
                $errors[] = "Line " . ($index + 1) . ": Error - " . $e->getMessage();
                Log::error('Bulk track import error', [
                    'line' => $line,
                    'line_number' => $index + 1,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Bulk track processing completed', [
            'processed' => $processedCount,
            'errors' => count($errors)
        ]);
        
        return [
            'processed' => $processedCount,
            'errors' => $errors
        ];
    }
}
