<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class YouTubeAnalyticsController extends Controller
{
    public function __construct(
        private readonly YouTubeService $youtubeService
    ) {}

    /**
     * Display the analytics dashboard.
     */
    public function index(): View
    {
        try {
            // Get analytics summary
            $summary = $this->youtubeService->getAnalyticsSummary();
            
            // Get tracks with analytics for the table
            $tracks = Track::uploadedToYoutube()
                          ->withAnalytics()
                          ->orderByViews()
                          ->paginate(20);
            
            // Get tracks that need analytics updates
            $staleTracksCount = Track::uploadedToYoutube()
                                   ->where(function ($q) {
                                       $q->whereNull('youtube_analytics_updated_at')
                                         ->orWhere('youtube_analytics_updated_at', '<', now()->subHour());
                                   })
                                   ->count();
            
            return view('youtube.analytics', [
                'summary' => $summary,
                'tracks' => $tracks,
                'staleTracksCount' => $staleTracksCount,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load analytics dashboard', [
                'error' => $e->getMessage(),
            ]);
            
            return view('youtube.analytics', [
                'summary' => [
                    'total_tracks' => 0,
                    'total_views' => 0,
                    'total_likes' => 0,
                    'total_comments' => 0,
                    'average_engagement_rate' => 0,
                    'top_performing' => [],
                    'recent_uploads' => [],
                ],
                'tracks' => collect(),
                'staleTracksCount' => 0,
                'error' => 'Failed to load analytics data: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get analytics summary as JSON.
     */
    public function summary(): JsonResponse
    {
        try {
            $summary = $this->youtubeService->getAnalyticsSummary();
            
            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get analytics summary via API', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get analytics summary',
            ], 500);
        }
    }

    /**
     * Get detailed analytics for a specific track.
     */
    public function trackAnalytics(Request $request, Track $track): JsonResponse
    {
        try {
            if (!$track->youtube_video_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Track is not uploaded to YouTube',
                ], 400);
            }
            
            $analytics = $this->youtubeService->getVideoAnalytics($track->youtube_video_id);
            
            if (!$analytics) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve analytics for this track',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get track analytics', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve track analytics',
            ], 500);
        }
    }

    /**
     * Update analytics for a specific track.
     */
    public function updateTrackAnalytics(Request $request, Track $track): JsonResponse
    {
        try {
            if (!$track->youtube_video_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Track is not uploaded to YouTube',
                ], 400);
            }
            
            if (!$this->youtubeService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'YouTube API not authenticated',
                ], 401);
            }
            
            $success = $this->youtubeService->updateTrackAnalytics($track);
            
            if ($success) {
                // Refresh the track to get updated data
                $track->refresh();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Analytics updated successfully',
                    'track' => [
                        'id' => $track->id,
                        'title' => $track->title,
                        'youtube_view_count' => $track->youtube_view_count,
                        'youtube_like_count' => $track->youtube_like_count,
                        'youtube_comment_count' => $track->youtube_comment_count,
                        'youtube_analytics_updated_at' => $track->youtube_analytics_updated_at?->toISOString(),
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update analytics',
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update track analytics', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update analytics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update analytics for all uploaded tracks.
     */
    public function bulkUpdateAnalytics(Request $request): JsonResponse
    {
        try {
            if (!$this->youtubeService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'YouTube API not authenticated',
                ], 401);
            }
            
            // Get tracks to update
            $staleOnly = $request->boolean('stale_only', true);
            
            $query = Track::uploadedToYoutube();
            
            if ($staleOnly) {
                $query->where(function ($q) {
                    $q->whereNull('youtube_analytics_updated_at')
                      ->orWhere('youtube_analytics_updated_at', '<', now()->subHour());
                });
            }
            
            $tracks = $query->get();
            
            if ($tracks->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tracks need updating',
                    'results' => [
                        'updated' => 0,
                        'failed' => 0,
                        'skipped' => 0,
                    ],
                ]);
            }
            
            // Update analytics in batches
            $results = $this->youtubeService->bulkUpdateAnalytics($tracks);
            
            return response()->json([
                'success' => true,
                'message' => "Analytics updated for {$results['updated']} tracks",
                'results' => $results,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to bulk update analytics', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update analytics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get top performing tracks.
     */
    public function topPerforming(Request $request): JsonResponse
    {
        try {
            $limit = $request->integer('limit', 10);
            $orderBy = $request->string('order_by', 'views'); // views, likes, engagement
            
            $query = Track::uploadedToYoutube()->withAnalytics();
            
            switch ($orderBy) {
                case 'likes':
                    $query->orderByLikes();
                    break;
                case 'engagement':
                    $query->orderByDesc('youtube_comment_count')
                          ->orderByDesc('youtube_like_count');
                    break;
                default:
                    $query->orderByViews();
                    break;
            }
            
            $tracks = $query->take($limit)->get()->map(function ($track) {
                return [
                    'id' => $track->id,
                    'title' => $track->title,
                    'youtube_video_id' => $track->youtube_video_id,
                    'view_count' => $track->youtube_view_count,
                    'like_count' => $track->youtube_like_count,
                    'comment_count' => $track->youtube_comment_count,
                    'uploaded_at' => $track->youtube_uploaded_at?->toISOString(),
                    'analytics_updated_at' => $track->youtube_analytics_updated_at?->toISOString(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $tracks,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get top performing tracks', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get top performing tracks',
            ], 500);
        }
    }
}
