<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\YouTubeAccount;
use App\Services\YouTubeBulkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

final class YouTubeBulkController extends Controller
{
    public function __construct(
        private readonly YouTubeBulkService $bulkService
    ) {}

    /**
     * Show the bulk upload interface.
     */
    public function index()
    {
        $eligibleTracks = $this->bulkService->getEligibleTracks(100);
        $accounts = YouTubeAccount::withValidTokens()->get();
        $activeAccount = YouTubeAccount::getActive();
        $queueStatus = $this->bulkService->getQueueStatus();

        return view('youtube.bulk', [
            'eligibleTracks' => $eligibleTracks,
            'accounts' => $accounts,
            'activeAccount' => $activeAccount,
            'queueStatus' => $queueStatus,
        ]);
    }

    /**
     * Queue bulk upload for selected tracks.
     */
    public function queueUpload(Request $request)
    {
        $validated = $request->validate([
            'track_ids' => 'required|array|min:1',
            'track_ids.*' => 'exists:tracks,id',
            'account_id' => 'nullable|exists:youtube_accounts,id',
            'privacy_status' => ['required', Rule::in(['public', 'unlisted', 'private'])],
            'made_for_kids' => 'boolean',
            'is_short' => 'boolean',
            'category_id' => 'required|string',
        ]);

        try {
            $tracks = Track::whereIn('id', $validated['track_ids'])->get();
            
            // Handle account selection
            $account = null;
            if (!empty($validated['account_id'])) {
                $account = YouTubeAccount::find($validated['account_id']);
                if (!$account) {
                    return back()->with('error', 'Selected YouTube account not found.');
                }
            } else {
                // Use active account if no specific account selected
                $account = YouTubeAccount::getActive();
                if (!$account) {
                    return back()->with('error', 'No active YouTube account found. Please authenticate first.');
                }
            }

            // Verify account has valid token
            if ($account->isTokenExpired()) {
                return back()->with('error', 'YouTube account token has expired. Please re-authenticate.');
            }

            $uploadOptions = [
                'privacy_status' => $validated['privacy_status'],
                'made_for_kids' => $validated['made_for_kids'] ?? false,
                'is_short' => $validated['is_short'] ?? false,
                'category_id' => $validated['category_id'],
            ];

            $results = $this->bulkService->queueBulkUpload($tracks, $account, $uploadOptions);

            $message = "Queued {$results['queued']} tracks for upload.";
            if ($results['skipped'] > 0) {
                $message .= " Skipped {$results['skipped']} tracks.";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Bulk upload queue failed', [
                'error' => $e->getMessage(),
                'track_count' => count($validated['track_ids'] ?? []),
                'account_id' => $validated['account_id'] ?? 'none',
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to queue uploads: ' . $e->getMessage());
        }
    }

    /**
     * Upload selected tracks immediately (synchronous).
     */
    public function uploadNow(Request $request)
    {
        $validated = $request->validate([
            'track_ids' => 'required|array|min:1|max:10', // Limit for sync uploads
            'track_ids.*' => 'exists:tracks,id',
            'account_id' => 'nullable|exists:youtube_accounts,id',
            'privacy_status' => ['required', Rule::in(['public', 'unlisted', 'private'])],
            'made_for_kids' => 'boolean',
            'is_short' => 'boolean',
            'category_id' => 'required|string',
        ]);

        try {
            $tracks = Track::whereIn('id', $validated['track_ids'])->get();
            
            // Handle account selection
            $account = null;
            if (!empty($validated['account_id'])) {
                $account = YouTubeAccount::find($validated['account_id']);
                if (!$account) {
                    return back()->with('error', 'Selected YouTube account not found.');
                }
            } else {
                // Use active account if no specific account selected
                $account = YouTubeAccount::getActive();
                if (!$account) {
                    return back()->with('error', 'No active YouTube account found. Please authenticate first.');
                }
            }

            // Verify account has valid token
            if ($account->isTokenExpired()) {
                return back()->with('error', 'YouTube account token has expired. Please re-authenticate.');
            }

            $uploadOptions = [
                'privacy_status' => $validated['privacy_status'],
                'made_for_kids' => $validated['made_for_kids'] ?? false,
                'is_short' => $validated['is_short'] ?? false,
                'category_id' => $validated['category_id'],
            ];

            $results = $this->bulkService->uploadBatch($tracks, $account, $uploadOptions);

            $message = "Successfully uploaded {$results['successful']} tracks.";
            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} uploads failed.";
            }
            if ($results['skipped'] > 0) {
                $message .= " {$results['skipped']} tracks were skipped.";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Immediate bulk upload failed', [
                'error' => $e->getMessage(),
                'track_count' => count($validated['track_ids'] ?? []),
                'account_id' => $validated['account_id'] ?? 'none',
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to upload tracks: ' . $e->getMessage());
        }
    }

    /**
     * Get queue status as JSON.
     */
    public function queueStatus()
    {
        try {
            $status = $this->bulkService->getQueueStatus();
            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retry failed uploads.
     */
    public function retryFailed()
    {
        try {
            $retried = $this->bulkService->retryFailedUploads();
            
            return back()->with('success', "Retried {$retried} failed uploads.");
        } catch (\Exception $e) {
            Log::error('Failed to retry uploads', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to retry uploads: ' . $e->getMessage());
        }
    }

    /**
     * Get eligible tracks for upload as JSON.
     */
    public function eligibleTracks(Request $request)
    {
        $limit = $request->integer('limit', 50);
        $search = $request->string('search');

        try {
            $tracks = $this->bulkService->getEligibleTracks($limit);
            
            if ($search->isNotEmpty()) {
                $tracks = $tracks->filter(function ($track) use ($search) {
                    return str_contains(strtolower($track->title), strtolower($search));
                });
            }

            return response()->json([
                'tracks' => $tracks->map(function ($track) {
                    return [
                        'id' => $track->id,
                        'title' => $track->title,
                        'genres' => $track->genres_list,
                        'duration' => $track->duration,
                        'file_size' => $track->file_size_human,
                        'created_at' => $track->created_at->diffForHumans(),
                    ];
                }),
                'total' => $tracks->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
