<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTrack;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

class TrackController extends Controller
{
    /**
     * Get the current status of a track
     * 
     * @param Track $track
     * @return JsonResponse
     */
    public function status(Track $track): JsonResponse
    {
        $track->load('genres');
        
        return response()->json([
            'id' => $track->id,
            'title' => $track->title,
            'status' => $track->status,
            'progress' => $track->progress,
            'error_message' => $track->error_message,
            'genres' => $track->genres->pluck('name'),
            'mp3_path' => $track->mp3_path,
            'image_path' => $track->image_path,
            'mp4_path' => $track->mp4_path,
            'updated_at' => $track->updated_at->toIso8601String(),
        ]);
    }
    
    /**
     * Get status for multiple tracks at once
     * More efficient than making multiple API calls for individual tracks
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statusBulk(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:tracks,id'
            ]);
            
            $trackIds = $request->input('ids');
            $tracks = Track::with('genres')->whereIn('id', $trackIds)->get();
            
            $result = $tracks->map(function ($track) {
                return [
                    'id' => $track->id,
                    'title' => $track->title,
                    'status' => $track->status,
                    'progress' => $track->progress,
                    'error_message' => $track->error_message,
                    'genres' => $track->genres->pluck('name'),
                    'updated_at' => $track->updated_at->toIso8601String(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'tracks' => $result,
                'timestamp' => now()->toIso8601String(),
                'count' => $result->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get track statuses: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Start processing a track
     * 
     * @param Track $track
     * @param Request $request
     * @return JsonResponse
     */
    public function start(Track $track, Request $request): JsonResponse
    {
        // Check if we need to force redownload
        $forceRedownload = $request->input('force_redownload', false);
        
        // Only start if the track is in a state that can be started or if force_redownload is true
        if ($track->status === 'processing' && !$forceRedownload) {
            return response()->json([
                'success' => false,
                'message' => 'This track is already processing',
                'status' => $track->status,
            ], 422);
        }
        
        // If force_redownload, clear existing paths
        if ($forceRedownload) {
            // Delete existing files if they exist
            if ($track->mp3_path) {
                \Storage::disk('public')->delete($track->mp3_path);
            }
            
            if ($track->image_path) {
                \Storage::disk('public')->delete($track->image_path);
            }
            
            if ($track->mp4_path) {
                \Storage::disk('public')->delete($track->mp4_path);
            }
            
            // Clear paths in database
            $track->mp3_path = null;
            $track->image_path = null;
            $track->mp4_path = null;
        }
        
        // Reset track status to start processing
        $track->update([
            'status' => 'pending',
            'progress' => 0,
            'error_message' => null,
            'mp3_path' => $track->mp3_path,
            'image_path' => $track->image_path,
            'mp4_path' => $track->mp4_path,
        ]);
        
        // Dispatch the job
        ProcessTrack::dispatch($track);
        
        return response()->json([
            'success' => true,
            'message' => $forceRedownload 
                ? "Track '{$track->title}' has been queued for redownload and processing"
                : "Track '{$track->title}' has been queued for processing",
            'track' => [
                'id' => $track->id,
                'title' => $track->title,
                'status' => $track->status,
            ]
        ]);
    }
    
    /**
     * Stop processing a track
     * 
     * @param int $trackId
     * @return JsonResponse
     */
    public function stop($trackId): JsonResponse
    {
        try {
            $track = Track::findOrFail($trackId);
            
            // Only stop if the track is in processing or pending state
            if (!in_array($track->status, ['processing', 'pending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This track is not currently processing',
                    'status' => $track->status,
                ], 422);
            }
            
            // Attempt to remove from queue if pending
            if ($track->status === 'pending') {
                // We cannot directly remove jobs from the queue
                // Instead, we'll mark the track as stopped, and the job will check this status
            }
            
            // Mark as stopped
            $track->update([
                'status' => 'stopped',
                'error_message' => 'Processing was manually stopped',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Track '{$track->title}' processing has been stopped",
                'track' => [
                    'id' => $track->id,
                    'title' => $track->title,
                    'status' => $track->status,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Track with ID {$trackId} not found",
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Start processing all tracks or tracks with specific filter
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function startAll(Request $request): JsonResponse
    {
        try {
            // Apply filters if provided
            $query = Track::query();
            
            // Filter by status if specified
            if ($request->has('filter_status')) {
                $filterStatus = $request->input('filter_status');
                if (!empty($filterStatus)) {
                    $query->whereIn('status', is_array($filterStatus) ? $filterStatus : [$filterStatus]);
                }
            } else {
                // Default: exclude tracks that are already being processed or completed
                $query->whereNotIn('status', ['processing', 'completed']);
            }
            
            // Filter by genres if specified
            if ($request->has('filter_genres') && is_array($request->input('filter_genres'))) {
                $genres = $request->input('filter_genres');
                if (!empty($genres)) {
                    $query->whereHas('genres', function ($q) use ($genres) {
                        $q->whereIn('genres.id', $genres);
                    });
                }
            }
            
            // Count total tracks that match the criteria
            $totalCount = $query->count();
            
            if ($totalCount === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tracks to process',
                    'count' => 0
                ]);
            }
            
            $processed = [];
            $chunkSize = 100; // Process in chunks to avoid memory issues with large datasets
            
            // Process in chunks to avoid memory issues
            $query->orderBy('id')->chunk($chunkSize, function ($tracks) use (&$processed) {
                foreach ($tracks as $track) {
                    // Skip tracks that are already processing
                    if ($track->status === 'processing') {
                        continue;
                    }
                    
                    // Reset track status
                    $track->update([
                        'status' => 'pending',
                        'progress' => 0,
                        'error_message' => null,
                    ]);
                    
                    // Dispatch the job
                    ProcessTrack::dispatch($track);
                    
                    $processed[] = [
                        'id' => $track->id,
                        'title' => $track->title,
                        'status' => 'pending'
                    ];
                }
            });
            
            $processedCount = count($processed);
            
            return response()->json([
                'success' => true,
                'message' => "{$processedCount} tracks have been queued for processing",
                'count' => $processedCount,
                'tracks' => $processed
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to start tracks processing: " . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Stop all currently processing tracks or tracks with specific filter
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stopAll(Request $request): JsonResponse
    {
        try {
            // Apply filters if provided
            $query = Track::query();
            
            // Default to only stopping tracks that are processing or pending
            if ($request->has('filter_status')) {
                $filterStatus = $request->input('filter_status');
                if (!empty($filterStatus)) {
                    $query->whereIn('status', is_array($filterStatus) ? $filterStatus : [$filterStatus]);
                }
            } else {
                $query->whereIn('status', ['processing', 'pending']);
            }
            
            // Filter by genres if specified
            if ($request->has('filter_genres') && is_array($request->input('filter_genres'))) {
                $genres = $request->input('filter_genres');
                if (!empty($genres)) {
                    $query->whereHas('genres', function ($q) use ($genres) {
                        $q->whereIn('genres.id', $genres);
                    });
                }
            }
            
            // Count total tracks that match the criteria
            $totalCount = $query->count();
            
            if ($totalCount === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tracks are currently processing',
                    'count' => 0
                ]);
            }
            
            $stopped = [];
            $chunkSize = 100; // Process in chunks to avoid memory issues with large datasets
            
            // Process in chunks to avoid memory issues
            $query->orderBy('id')->chunk($chunkSize, function ($tracks) use (&$stopped) {
                foreach ($tracks as $track) {
                    // Only stop tracks that are in a valid state for stopping
                    if (!in_array($track->status, ['processing', 'pending'])) {
                        continue;
                    }
                    
                    // Mark as stopped
                    $track->update([
                        'status' => 'stopped',
                        'error_message' => 'Processing was manually stopped',
                    ]);
                    
                    $stopped[] = [
                        'id' => $track->id,
                        'title' => $track->title,
                        'status' => 'stopped'
                    ];
                }
            });
            
            $stoppedCount = count($stopped);
            
            return response()->json([
                'success' => true,
                'message' => "{$stoppedCount} tracks have been stopped",
                'count' => $stoppedCount,
                'tracks' => $stopped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to stop tracks: " . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Retry processing a failed track
     * 
     * @param Track $track
     * @return JsonResponse
     */
    public function retry(Track $track): JsonResponse
    {
        // Only retry if the track is in a failed state
        if ($track->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'This track is not in a failed state and cannot be retried',
                'status' => $track->status,
            ], 422);
        }
        
        // Reset track status
        $track->update([
            'status' => 'pending',
            'progress' => 0,
            'error_message' => null,
        ]);
        
        // Dispatch the job
        ProcessTrack::dispatch($track);
        
        return response()->json([
            'success' => true,
            'message' => "Track '{$track->title}' has been requeued for processing",
            'track' => [
                'id' => $track->id,
                'title' => $track->title,
                'status' => $track->status,
            ]
        ]);
    }
    
    /**
     * Retry processing all failed tracks or tracks with specific filter
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function retryAll(Request $request): JsonResponse
    {
        try {
            // Apply filters if provided
            $query = Track::query();
            
            // Default to only retry failed tracks
            if ($request->has('filter_status')) {
                $filterStatus = $request->input('filter_status');
                if (!empty($filterStatus)) {
                    $query->whereIn('status', is_array($filterStatus) ? $filterStatus : [$filterStatus]);
                }
            } else {
                $query->where('status', 'failed');
            }
            
            // Filter by genres if specified
            if ($request->has('filter_genres') && is_array($request->input('filter_genres'))) {
                $genres = $request->input('filter_genres');
                if (!empty($genres)) {
                    $query->whereHas('genres', function ($q) use ($genres) {
                        $q->whereIn('genres.id', $genres);
                    });
                }
            }
            
            // Count total tracks that match the criteria
            $totalCount = $query->count();
            
            if ($totalCount === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No failed tracks to retry',
                    'count' => 0
                ]);
            }
            
            $processed = [];
            $chunkSize = 100; // Process in chunks to avoid memory issues with large datasets
            
            // Process in chunks to avoid memory issues
            $query->orderBy('id')->chunk($chunkSize, function ($tracks) use (&$processed) {
                foreach ($tracks as $track) {
                    // Reset track status
                    $track->update([
                        'status' => 'pending',
                        'progress' => 0,
                        'error_message' => null,
                    ]);
                    
                    // Dispatch the job
                    ProcessTrack::dispatch($track);
                    
                    $processed[] = [
                        'id' => $track->id,
                        'title' => $track->title,
                        'status' => 'pending'
                    ];
                }
            });
            
            $processedCount = count($processed);
            
            return response()->json([
                'success' => true,
                'message' => "{$processedCount} failed tracks have been requeued for processing",
                'count' => $processedCount,
                'tracks' => $processed
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to retry tracks: " . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Perform bulk actions on tracks
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'action' => 'required|string|in:start,stop,retry,delete',
                'track_ids' => 'required|array',
                'track_ids.*' => 'integer|exists:tracks,id'
            ]);
            
            $action = $request->input('action');
            $trackIds = $request->input('track_ids');
            
            if (empty($trackIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tracks selected for the action',
                ], 422);
            }
            
            $tracks = Track::whereIn('id', $trackIds)->get();
            $processed = [];
            $skipped = [];
            $count = 0;
            
            foreach ($tracks as $track) {
                $result = $this->performActionOnTrack($track, $action);
                
                if ($result['success']) {
                    $processed[] = [
                        'id' => $track->id,
                        'title' => $track->title,
                        'status' => $track->status
                    ];
                    $count++;
                } else {
                    $skipped[] = [
                        'id' => $track->id,
                        'title' => $track->title,
                        'reason' => $result['message']
                    ];
                }
            }
            
            $actionVerb = $this->getActionVerb($action);
            
            return response()->json([
                'success' => true,
                'message' => "{$count} tracks have been {$actionVerb}",
                'count' => $count,
                'processed' => $processed,
                'skipped' => $skipped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to perform bulk action: " . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Perform action on a single track
     * 
     * @param Track $track
     * @param string $action
     * @return array
     */
    protected function performActionOnTrack(Track $track, string $action): array
    {
        switch ($action) {
            case 'start':
                if ($track->status === 'processing') {
                    return ['success' => false, 'message' => 'Track is already processing'];
                }
                
                $track->update([
                    'status' => 'pending',
                    'progress' => 0,
                    'error_message' => null,
                ]);
                
                ProcessTrack::dispatch($track);
                return ['success' => true];
                
            case 'stop':
                if (!in_array($track->status, ['processing', 'pending'])) {
                    return ['success' => false, 'message' => 'Track is not currently processing'];
                }
                
                $track->update([
                    'status' => 'stopped',
                    'error_message' => 'Processing was manually stopped',
                ]);
                return ['success' => true];
                
            case 'retry':
                if ($track->status !== 'failed' && $track->status !== 'stopped') {
                    return ['success' => false, 'message' => 'Track is not in a failed or stopped state'];
                }
                
                $track->update([
                    'status' => 'pending',
                    'progress' => 0,
                    'error_message' => null,
                ]);
                
                ProcessTrack::dispatch($track);
                return ['success' => true];
                
            case 'delete':
                // First check if track has associated media files
                $this->deleteTrackMedia($track);
                
                // Then delete the track record
                $track->delete();
                return ['success' => true];
                
            default:
                return ['success' => false, 'message' => 'Invalid action specified'];
        }
    }
    
    /**
     * Get past tense verb for action for response message
     * 
     * @param string $action
     * @return string
     */
    protected function getActionVerb(string $action): string
    {
        switch ($action) {
            case 'start': return 'started';
            case 'stop': return 'stopped';
            case 'retry': return 'requeued';
            case 'delete': return 'deleted';
            default: return $action . 'ed';
        }
    }
    
    /**
     * Delete associated media files for a track
     * 
     * @param Track $track
     * @return void
     */
    protected function deleteTrackMedia(Track $track): void
    {
        // Delete media files if they exist
        if (!empty($track->mp3_path)) {
            \Storage::disk('public')->delete($track->mp3_path);
        }
        
        if (!empty($track->image_path)) {
            \Storage::disk('public')->delete($track->image_path);
        }
        
        if (!empty($track->mp4_path)) {
            \Storage::disk('public')->delete($track->mp4_path);
        }
    }
} 