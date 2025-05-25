<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Track;
use App\Models\YouTubeAccount;
use App\Services\YouTubeService;
use Illuminate\Console\Command;

final class YouTubeAnalyticsDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:dashboard 
                            {--account-id= : Use a specific YouTube account}
                            {--refresh : Refresh analytics before showing dashboard}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display YouTube analytics dashboard';

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
        $this->info('📊 YouTube Analytics Dashboard');
        $this->newLine();

        // Set up YouTube account if needed for refresh
        if ($this->option('refresh')) {
            $accountId = $this->option('account-id');
            $account = $accountId ? YouTubeAccount::find($accountId) : YouTubeAccount::getActive();

            if (!$account) {
                $this->warn('No YouTube account found for refresh. Showing cached data only.');
            } else {
                if ($this->youtubeService->setAccount($account)) {
                    $this->info('🔄 Refreshing analytics data...');
                    
                    $tracks = Track::uploadedToYoutube()->take(20)->get();
                    if ($tracks->isNotEmpty()) {
                        $results = $this->youtubeService->bulkUpdateAnalytics($tracks);
                        $this->line("Updated {$results['updated']} tracks");
                    }
                    $this->newLine();
                }
            }
        }

        // Get analytics summary
        $summary = $this->youtubeService->getAnalyticsSummary();

        // Display overview
        $this->displayOverview($summary);
        $this->newLine();

        // Display top performing tracks
        $this->displayTopPerforming($summary['top_performing']);
        $this->newLine();

        // Display recent uploads
        $this->displayRecentUploads($summary['recent_uploads']);
        $this->newLine();

        // Display detailed statistics
        $this->displayDetailedStats();

        return 0;
    }

    private function displayOverview(array $summary): void
    {
        $this->info('📈 Overview');
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Uploaded Tracks', number_format($summary['total_tracks'])],
                ['Total Views', number_format($summary['total_views'])],
                ['Total Likes', number_format($summary['total_likes'])],
                ['Total Comments', number_format($summary['total_comments'])],
                ['Average Engagement Rate', $summary['average_engagement_rate'] . '%'],
            ]
        );
    }

    private function displayTopPerforming(array $topPerforming): void
    {
        $this->info('🏆 Top Performing Tracks');
        
        if (empty($topPerforming)) {
            $this->line('No data available');
            return;
        }

        $tableData = [];
        foreach ($topPerforming as $track) {
            $tableData[] = [
                substr($track['title'], 0, 40) . (strlen($track['title']) > 40 ? '...' : ''),
                number_format($track['views']),
                number_format($track['likes']),
                $track['engagement_rate'] . '%',
            ];
        }

        $this->table(
            ['Title', 'Views', 'Likes', 'Engagement'],
            $tableData
        );
    }

    private function displayRecentUploads(array $recentUploads): void
    {
        $this->info('🆕 Recent Uploads (Last 7 Days)');
        
        if (empty($recentUploads)) {
            $this->line('No recent uploads');
            return;
        }

        $tableData = [];
        foreach ($recentUploads as $track) {
            $tableData[] = [
                substr($track['title'], 0, 40) . (strlen($track['title']) > 40 ? '...' : ''),
                number_format($track['views']),
                $track['uploaded_at']->format('M j, Y'),
            ];
        }

        $this->table(
            ['Title', 'Views', 'Uploaded'],
            $tableData
        );
    }

    private function displayDetailedStats(): void
    {
        $this->info('📊 Detailed Statistics');

        // Get tracks by status
        $totalTracks = Track::count();
        $completedTracks = Track::where('status', 'completed')->count();
        $uploadedTracks = Track::uploadedToYoutube()->count();
        $processingTracks = Track::whereIn('status', ['pending', 'processing'])->count();

        // Get upload statistics
        $uploadsToday = Track::uploadedToYoutube()
            ->whereDate('youtube_uploaded_at', today())
            ->count();

        $uploadsThisWeek = Track::uploadedToYoutube()
            ->where('youtube_uploaded_at', '>=', now()->startOfWeek())
            ->count();

        $uploadsThisMonth = Track::uploadedToYoutube()
            ->where('youtube_uploaded_at', '>=', now()->startOfMonth())
            ->count();

        // Get view statistics
        $totalViews = Track::uploadedToYoutube()->sum('youtube_view_count');
        $avgViews = Track::uploadedToYoutube()->avg('youtube_view_count');
        $maxViews = Track::uploadedToYoutube()->max('youtube_view_count');

        $this->table(
            ['Category', 'Metric', 'Value'],
            [
                ['Tracks', 'Total Tracks', number_format($totalTracks)],
                ['Tracks', 'Completed Tracks', number_format($completedTracks)],
                ['Tracks', 'Uploaded to YouTube', number_format($uploadedTracks)],
                ['Tracks', 'Processing', number_format($processingTracks)],
                ['', '', ''],
                ['Uploads', 'Today', number_format($uploadsToday)],
                ['Uploads', 'This Week', number_format($uploadsThisWeek)],
                ['Uploads', 'This Month', number_format($uploadsThisMonth)],
                ['', '', ''],
                ['Views', 'Total Views', number_format($totalViews)],
                ['Views', 'Average Views', number_format($avgViews ?? 0)],
                ['Views', 'Highest Views', number_format($maxViews ?? 0)],
            ]
        );

        // Show upload rate
        if ($uploadedTracks > 0) {
            $uploadRate = ($uploadedTracks / $completedTracks) * 100;
            $this->newLine();
            $this->line("📤 Upload Rate: " . number_format($uploadRate, 1) . "% of completed tracks uploaded");
        }

        // Show engagement insights
        $highEngagementTracks = Track::uploadedToYoutube()
            ->whereRaw('(youtube_like_count + youtube_comment_count) / CASE WHEN youtube_view_count > 0 THEN youtube_view_count ELSE 1 END * 100 > 5')
            ->count();

        if ($highEngagementTracks > 0) {
            $this->line("🎯 High Engagement: {$highEngagementTracks} tracks with >5% engagement rate");
        }
    }
} 