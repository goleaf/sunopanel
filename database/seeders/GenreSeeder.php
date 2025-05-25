<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class GenreSeeder extends Seeder
{
    /**
     * Essential music genres for the application.
     */
    private array $genres = [
        'City Pop',
        'Synthwave',
        'Lo-Fi',
        'Jazz',
        'Electronic',
        'Ambient',
        'Rock',
        'Pop',
        'Classical',
        'Hip Hop',
        'R&B',
        'Funk',
        'Soul',
        'Blues',
        'Country',
        'Folk',
        'Indie',
        'Alternative',
        'Punk',
        'Metal',
        'Reggae',
        'Ska',
        'Disco',
        'House',
        'Techno',
        'Trance',
        'Drum & Bass',
        'Dubstep',
        'Trap',
        'Vaporwave',
        'Chillwave',
        'New Age',
        'World Music',
        'Latin',
        'Bossa Nova',
        'Swing',
        'Bebop',
        'Fusion',
        'Progressive',
        'Experimental',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding genres...');

        foreach ($this->genres as $index => $genreName) {
            $slug = Str::slug($genreName);
            $existing = Genre::where('name', $genreName)
                            ->orWhere('slug', $slug)
                            ->first();
            if (!$existing) {
                // Find the next available genre_id
                $maxGenreId = (int) (Genre::max('genre_id') ?? 0);
                
                Genre::create([
                    'name' => $genreName,
                    'slug' => $slug,
                    'genre_id' => $maxGenreId + 1,
                ]);
                
                $this->command->info("Created genre: {$genreName}");
            } else {
                $this->command->info("Genre already exists: {$genreName}");
            }
        }

        $this->command->info('Created ' . count($this->genres) . ' genres.');
    }
}
