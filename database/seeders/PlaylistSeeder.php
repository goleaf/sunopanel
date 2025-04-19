<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Database\Seeder;

class PlaylistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing playlists to avoid duplication
        Playlist::truncate();

        // Get all genres with their track counts
        $genres = Genre::withCount('tracks')->get();

        // Sort genres by track count and take top 5 with more than one track
        $popularGenres = $genres->filter(function ($genre) {
            return $genre->tracks_count > 1;
        })->sortByDesc('tracks_count')->take(5);

        // Create a playlist for each popular genre
        foreach ($popularGenres as $genre) {
            $playlist = Playlist::create([
                'name' => $genre->name.' Collection',
                'description' => 'A curated collection of '.$genre->name.' tracks',
                'genre_id' => $genre->id,
                'cover_image' => $this->getRandomCoverForGenre($genre),
            ]);

            // Add tracks from this genre to the playlist
            $tracks = $genre->tracks()->inRandomOrder()->limit(min($genre->tracks_count, 10))->get();

            foreach ($tracks as $index => $track) {
                $playlist->addTrack($track, $index);
            }
        }

        // Create a mixed playlist
        $mixedPlaylist = Playlist::create([
            'name' => 'Ultimate Mix',
            'description' => 'A diverse collection of tracks across multiple genres',
            'cover_image' => 'https://cdn2.suno.ai/image_c20d86aa-3936-4f0d-86c4-6e52f802aaa6.jpeg',
        ]);

        // Add tracks from various genres
        $tracks = Track::inRandomOrder()->limit(12)->get();

        foreach ($tracks as $index => $track) {
            $mixedPlaylist->addTrack($track, $index);
        }

        // Create a trending playlist
        $trendingPlaylist = Playlist::create([
            'name' => 'Trending Now',
            'description' => 'The hottest tracks right now',
            'cover_image' => 'https://cdn2.suno.ai/0243cd63-807b-4d4f-a110-42afb2c4233d_30a25352.jpeg',
        ]);

        // Add recent tracks
        $recentTracks = Track::orderBy('created_at', 'desc')->limit(8)->get();

        foreach ($recentTracks as $index => $track) {
            $trendingPlaylist->addTrack($track, $index);
        }
    }

    /**
     * Get a random cover image for a genre from one of its tracks
     */
    private function getRandomCoverForGenre(Genre $genre): string
    {
        $track = $genre->tracks()->inRandomOrder()->first();

        return $track ? $track->image_url : 'https://via.placeholder.com/300';
    }
}
