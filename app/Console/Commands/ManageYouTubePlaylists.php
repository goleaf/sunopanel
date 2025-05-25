<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Track;
use App\Models\YouTubeAccount;
use App\Services\YouTubeService;
use Illuminate\Console\Command;

final class ManageYouTubePlaylists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:playlists 
                            {action : Action to perform (list, create, add-tracks, organize)}
                            {--account-id= : Use a specific YouTube account}
                            {--title= : Playlist title for create action}
                            {--description= : Playlist description for create action}
                            {--privacy=public : Playlist privacy (public, unlisted, private)}
                            {--playlist-id= : Playlist ID for add-tracks action}
                            {--genre= : Genre filter for organizing tracks}
                            {--limit=50 : Limit number of tracks to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage YouTube playlists and organize tracks';

    public function __construct(
        private readonly YouTubeService $youtubeService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        // Set up YouTube account
        $accountId = $this->option('account-id');
        $account = $accountId ? YouTubeAccount::find($accountId) : YouTubeAccount::getActive();

        if (!$account) {
            $this->error('No YouTube account found. Please specify --account-id or ensure an account is active.');
            return 1;
        }

        if (!$this->youtubeService->setAccount($account)) {
            $this->error('Failed to set YouTube account.');
            return 1;
        }

        // Check account status
        $status = $this->youtubeService->checkAccountStatus();
        if (!$status['can_upload']) {
            $this->error("YouTube account has issues: {$status['reason']}");
            return 1;
        }

        $this->info("✅ Using account: {$account->name}");

        return match ($action) {
            'list' => $this->listPlaylists(),
            'create' => $this->createPlaylist(),
            'add-tracks' => $this->addTracksToPlaylist(),
            'organize' => $this->organizeTracksByGenre(),
            default => $this->error("Unknown action: {$action}. Available actions: list, create, add-tracks, organize")
        };
    }

    private function listPlaylists(): int
    {
        $this->info('📋 YouTube Playlists');
        $this->newLine();

        $playlists = $this->youtubeService->getPlaylists();

        if (empty($playlists)) {
            $this->line('No playlists found.');
            return 0;
        }

        $tableData = [];
        foreach ($playlists as $id => $title) {
            $tableData[] = [
                substr($id, 0, 20) . '...',
                $title,
                "https://www.youtube.com/playlist?list={$id}"
            ];
        }

        $this->table(
            ['Playlist ID', 'Title', 'URL'],
            $tableData
        );

        $this->info("Total playlists: " . count($playlists));

        return 0;
    }

    private function createPlaylist(): int
    {
        $title = $this->option('title');
        $description = $this->option('description') ?? '';
        $privacy = $this->option('privacy');

        if (!$title) {
            $title = $this->ask('Enter playlist title:');
        }

        if (!$title) {
            $this->error('Playlist title is required.');
            return 1;
        }

        $this->info("Creating playlist: {$title}");

        $playlistId = $this->youtubeService->findOrCreatePlaylist($title, $description, $privacy);

        if ($playlistId) {
            $this->info("✅ Playlist created successfully!");
            $this->line("Playlist ID: {$playlistId}");
            $this->line("URL: https://www.youtube.com/playlist?list={$playlistId}");
            return 0;
        } else {
            $this->error("❌ Failed to create playlist.");
            return 1;
        }
    }

    private function addTracksToPlaylist(): int
    {
        $playlistId = $this->option('playlist-id');
        $genre = $this->option('genre');
        $limit = (int) $this->option('limit');

        if (!$playlistId) {
            $playlistId = $this->ask('Enter playlist ID:');
        }

        if (!$playlistId) {
            $this->error('Playlist ID is required.');
            return 1;
        }

        // Get tracks to add
        $query = Track::uploadedToYoutube();

        if ($genre) {
            $query->whereHas('genre', function ($q) use ($genre) {
                $q->where('name', 'like', "%{$genre}%");
            });
        }

        $tracks = $query->limit($limit)->get();

        if ($tracks->isEmpty()) {
            $this->warn('No tracks found to add to playlist.');
            return 0;
        }

        $this->info("Adding {$tracks->count()} tracks to playlist...");

        $progressBar = $this->output->createProgressBar($tracks->count());
        $progressBar->start();

        $added = 0;
        $failed = 0;

        foreach ($tracks as $track) {
            if ($this->youtubeService->addVideoToPlaylist($track->youtube_video_id, $playlistId)) {
                $added++;
            } else {
                $failed++;
            }
            $progressBar->advance();
            
            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Added {$added} tracks to playlist");
        if ($failed > 0) {
            $this->warn("❌ Failed to add {$failed} tracks");
        }

        return $failed > 0 ? 1 : 0;
    }

    private function organizeTracksByGenre(): int
    {
        $this->info('🎵 Organizing tracks by genre into playlists...');

        // Get all genres that have uploaded tracks
        $genres = Track::uploadedToYoutube()
                      ->with('genre')
                      ->get()
                      ->pluck('genre')
                      ->filter()
                      ->unique('id')
                      ->sortBy('name');

        if ($genres->isEmpty()) {
            $this->warn('No genres found with uploaded tracks.');
            return 0;
        }

        $this->info("Found {$genres->count()} genres with uploaded tracks");

        foreach ($genres as $genre) {
            $this->line("Processing genre: {$genre->name}");

            // Create or find playlist for this genre
            $playlistTitle = "SunoPanel - {$genre->name}";
            $playlistDescription = "Automatically generated playlist for {$genre->name} tracks from SunoPanel";
            
            $playlistId = $this->youtubeService->findOrCreatePlaylist(
                $playlistTitle,
                $playlistDescription,
                'public'
            );

            if (!$playlistId) {
                $this->error("Failed to create playlist for genre: {$genre->name}");
                continue;
            }

            // Get tracks for this genre
            $tracks = Track::uploadedToYoutube()
                          ->where('genre_id', $genre->id)
                          ->limit(50) // Limit to avoid overwhelming the playlist
                          ->get();

            $this->line("  Adding {$tracks->count()} tracks to playlist...");

            $added = 0;
            foreach ($tracks as $track) {
                if ($this->youtubeService->addVideoToPlaylist($track->youtube_video_id, $playlistId)) {
                    $added++;
                }
                
                // Small delay to avoid rate limiting
                usleep(300000); // 0.3 seconds
            }

            $this->info("  ✅ Added {$added} tracks to '{$playlistTitle}'");
        }

        $this->newLine();
        $this->info('🎉 Genre organization completed!');

        return 0;
    }
} 