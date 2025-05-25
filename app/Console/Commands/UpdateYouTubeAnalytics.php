<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Track;
use App\Services\YouTubeService;
use App\Services\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class UpdateYouTubeAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'youtube:update-analytics 
                            {--limit=50 : Maximum number of tracks to update}
                            {--force : Force update all tracks regardless of last update time}
                            {--stale-only : Only update tracks with stale analytics (default)}
                            {--track= : Update specific track by ID}';

    /**
     * The console command description.
     */
    protected $description = 'Update YouTube analytics for uploaded tracks';

    public function __construct(
        private readonly YouTubeService $youtubeService,
        private readonly CacheService $cacheService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting YouTube analytics update...');

        try {
            // Check if YouTube service is authenticated
            if (!$this->youtubeService->isAuthenticated()) {
                $this->error('YouTube API is not authenticated. Please authenticate first.');
                return self::FAILURE;
            }

            $limit = (int) $this->option('limit');
            $force = $this->option('force');
            $staleOnly = $this->option('stale-only') || !$force;
            $trackId = $this->option('track');

            // Handle specific track update
            if ($trackId) {
                return $this->updateSpecificTrack((int) $trackId);
            }

            // Get tracks to update
            $query = Track::uploadedToYoutube();

            if ($staleOnly && !$force) {
                $query->where(function ($q) {
                    $q->whereNull('youtube_analytics_updated_at')
                      ->orWhere('youtube_analytics_updated_at', '<', now()->subHour());
                });
                $this->info('Updating only tracks with stale analytics (older than 1 hour)');
            } else {
                $this->info('Force updating all tracks');
            }

            $tracks = $query->take($limit)->get();

            if ($tracks->isEmpty()) {
                $this->info('No tracks need analytics updates.');
                return self::SUCCESS;
            }

            $this->info("Found {$tracks->count()} tracks to update");

            // Create progress bar
            $progressBar = $this->output->createProgressBar($tracks->count());
            $progressBar->start();

            // Update analytics in batches
            $results = $this->youtubeService->bulkUpdateAnalytics($tracks);

            $progressBar->finish();
            $this->newLine(2);

            // Clear relevant caches
            $this->clearAnalyticsCache();

            // Display results
            $this->displayResults($results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to update analytics: ' . $e->getMessage());
            Log::error('YouTube analytics update command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Update analytics for a specific track.
     */
    private function updateSpecificTrack(int $trackId): int
    {
        try {
            $track = Track::findOrFail($trackId);

            if (!$track->youtube_video_id) {
                $this->error("Track '{$track->title}' is not uploaded to YouTube");
                return self::FAILURE;
            }

            $this->info("Updating analytics for track: {$track->title}");

            $success = $this->youtubeService->updateTrackAnalytics($track);

            if ($success) {
                $track->refresh();
                $this->info('Analytics updated successfully:');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Views', number_format($track->youtube_view_count ?? 0)],
                        ['Likes', number_format($track->youtube_like_count ?? 0)],
                        ['Comments', number_format($track->youtube_comment_count ?? 0)],
                        ['Engagement Rate', ($track->engagement_rate ?? 0) . '%'],
                        ['Last Updated', $track->youtube_analytics_updated_at?->format('Y-m-d H:i:s') ?? 'Never'],
                    ]
                );

                // Clear cache for this track
                $this->cacheService->forget("youtube:analytics:track:{$track->id}");
                $this->clearAnalyticsCache();

                return self::SUCCESS;
            } else {
                $this->error('Failed to update analytics for this track');
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('Error updating track analytics: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Display update results.
     */
    private function displayResults(array $results): void
    {
        $this->info('Analytics update completed!');
        
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $results['updated']],
                ['Failed', $results['failed']],
                ['Skipped', $results['skipped']],
                ['Total', $results['updated'] + $results['failed'] + $results['skipped']],
            ]
        );

        if ($results['updated'] > 0) {
            $this->info("✅ Successfully updated {$results['updated']} tracks");
        }

        if ($results['failed'] > 0) {
            $this->warn("⚠️  Failed to update {$results['failed']} tracks");
        }

        if ($results['skipped'] > 0) {
            $this->info("⏭️  Skipped {$results['skipped']} tracks");
        }

        // Show some statistics
        $this->showAnalyticsSummary();
    }

    /**
     * Show analytics summary.
     */
    private function showAnalyticsSummary(): void
    {
        try {
            $summary = $this->youtubeService->getAnalyticsSummary();

            $this->newLine();
            $this->info('📊 Current Analytics Summary:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Tracks', number_format($summary['total_tracks'])],
                    ['Total Views', number_format($summary['total_views'])],
                    ['Total Likes', number_format($summary['total_likes'])],
                    ['Total Comments', number_format($summary['total_comments'])],
                    ['Avg. Engagement Rate', $summary['average_engagement_rate'] . '%'],
                ]
            );

            if (!empty($summary['top_performing'])) {
                $this->newLine();
                $this->info('🏆 Top Performing Tracks:');
                $topTracks = array_slice($summary['top_performing'], 0, 3);
                $this->table(
                    ['Title', 'Views', 'Likes', 'Engagement'],
                    array_map(function ($track) {
                        return [
                            substr($track['title'], 0, 30) . (strlen($track['title']) > 30 ? '...' : ''),
                            number_format($track['views']),
                            number_format($track['likes']),
                            $track['engagement_rate'] . '%',
                        ];
                    }, $topTracks)
                );
            }

        } catch (\Exception $e) {
            $this->warn('Could not load analytics summary: ' . $e->getMessage());
        }
    }

    /**
     * Clear analytics-related caches.
     */
    private function clearAnalyticsCache(): void
    {
        $cacheKeys = [
            'youtube:analytics:summary',
            'youtube:analytics:recent_tracks',
            'youtube:analytics:top_tracks',
        ];

        foreach ($cacheKeys as $key) {
            $this->cacheService->forget($key);
        }

        // Clear trend caches
        $periods = ['7d', '30d', '90d', '1y'];
        $metrics = ['views', 'likes', 'comments', 'engagement'];

        foreach ($periods as $period) {
            foreach ($metrics as $metric) {
                $this->cacheService->forget("youtube:analytics:trends:{$period}:{$metric}");
            }
        }

        // Clear top performing caches
        foreach ($metrics as $metric) {
            foreach ($periods as $period) {
                for ($limit = 5; $limit <= 50; $limit += 5) {
                    $this->cacheService->forget("youtube:analytics:top_performing:{$metric}:{$limit}:{$period}");
                }
            }
        }

        $this->info('Analytics caches cleared');
    }
}
