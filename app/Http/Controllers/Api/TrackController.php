<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTrack;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'tracks' => $result,
            'timestamp' => now()->toIso8601String(),
        ]);
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
     * Retry processing all failed tracks
     * 
     * @return JsonResponse
     */
    public function retryAll(): JsonResponse
    {
        // Get all failed tracks
        $failedTracks = Track::where('status', 'failed')->get();
        $count = $failedTracks->count();
        
        if ($count === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No failed tracks to retry',
                'count' => 0
            ]);
        }
        
        $processed = [];
        
        // Reset status and dispatch jobs for all failed tracks
        foreach ($failedTracks as $track) {
            $track->update([
                'status' => 'pending',
                'progress' => 0,
                'error_message' => null,
            ]);
            
            ProcessTrack::dispatch($track);
            
            $processed[] = [
                'id' => $track->id,
                'title' => $track->title
            ];
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$count} failed tracks have been requeued for processing",
            'count' => $count,
            'tracks' => $processed
        ]);
    }
} 