<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Track;
use App\Jobs\ProcessTrack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class TrackService
{
    /**
     * Start processing a track.
     */
    public function startProcessing(Track $track, bool $forceRedownload = false): bool
    {
        try {
            // Check if track is already processing or completed (unless force redownload)
            if (in_array($track->status, ['processing', 'completed']) && !$forceRedownload) {
                return false;
            }

            // If force redownload, clear existing files
            if ($forceRedownload) {
                $this->clearTrackFiles($track);
            }

            // Reset track status
            $track->update([
                'status' => 'pending',
                'progress' => 0,
                'error_message' => null,
                'mp3_path' => $forceRedownload ? null : $track->mp3_path,
                'image_path' => $forceRedownload ? null : $track->image_path,
                'mp4_path' => $forceRedownload ? null : $track->mp4_path,
            ]);

            // Dispatch the job
            ProcessTrack::dispatch($track);

            Log::info('Track processing started', [
                'track_id' => $track->id,
                'title' => $track->title,
                'force_redownload' => $forceRedownload,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to start track processing', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Stop processing a track.
     */
    public function stopProcessing(Track $track): bool
    {
        try {
            // Only stop if the track is in processing or pending state
            if (!in_array($track->status, ['processing', 'pending'])) {
                return false;
            }

            // Mark as stopped
            $track->update([
                'status' => 'stopped',
                'error_message' => 'Processing was manually stopped',
            ]);

            Log::info('Track processing stopped', [
                'track_id' => $track->id,
                'title' => $track->title,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to stop track processing', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retry processing a track.
     */
    public function retryProcessing(Track $track): bool
    {
        try {
            // Check if track is already processing or completed
            if ($track->status === 'processing') {
                return false;
            }

            if ($track->status === 'completed') {
                return false;
            }

            // Reset track status
            $track->update([
                'status' => 'pending',
                'progress' => 0,
                'error_message' => null,
            ]);

            // Dispatch the job
            ProcessTrack::dispatch($track);

            Log::info('Track processing retried', [
                'track_id' => $track->id,
                'title' => $track->title,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to retry track processing', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get track processing statistics.
     */
    public function getProcessingStats(): array
    {
        try {
            $stats = Track::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "stopped" THEN 1 ELSE 0 END) as stopped
            ')->first();

            return [
                'total' => $stats->total,
                'processing' => $stats->processing,
                'pending' => $stats->pending,
                'completed' => $stats->completed,
                'failed' => $stats->failed,
                'stopped' => $stats->stopped,
                'active' => $stats->processing + $stats->pending,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get processing stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'total' => 0,
                'processing' => 0,
                'pending' => 0,
                'completed' => 0,
                'failed' => 0,
                'stopped' => 0,
                'active' => 0,
            ];
        }
    }

    /**
     * Check if a track can be started.
     */
    public function canStart(Track $track): bool
    {
        return !in_array($track->status, ['processing', 'completed']);
    }

    /**
     * Check if a track can be stopped.
     */
    public function canStop(Track $track): bool
    {
        return in_array($track->status, ['processing', 'pending']);
    }

    /**
     * Check if a track can be retried.
     */
    public function canRetry(Track $track): bool
    {
        return in_array($track->status, ['failed', 'stopped']);
    }

    /**
     * Clear existing track files from storage.
     */
    private function clearTrackFiles(Track $track): void
    {
        try {
            // Delete existing files if they exist
            if ($track->mp3_path) {
                Storage::disk('public')->delete($track->mp3_path);
                Log::info('Deleted MP3 file for track redownload', [
                    'track_id' => $track->id,
                    'file_path' => $track->mp3_path,
                ]);
            }

            if ($track->image_path) {
                Storage::disk('public')->delete($track->image_path);
                Log::info('Deleted image file for track redownload', [
                    'track_id' => $track->id,
                    'file_path' => $track->image_path,
                ]);
            }

            if ($track->mp4_path) {
                Storage::disk('public')->delete($track->mp4_path);
                Log::info('Deleted MP4 file for track redownload', [
                    'track_id' => $track->id,
                    'file_path' => $track->mp4_path,
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to delete some track files during redownload', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
} 