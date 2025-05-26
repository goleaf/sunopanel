<?php

use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VideoUploadController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\YouTubeController;
use App\Http\Controllers\YouTubeBulkController;
use App\Http\Controllers\YouTubeAnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home routes (Add tracks)
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/process', [HomeController::class, 'process'])->name('home.process');
Route::post('/process-immediate', [HomeController::class, 'processImmediate'])->name('home.process.immediate');

// Tracks routes
Route::get('/tracks', [TrackController::class, 'index'])->name('tracks.index');
Route::get('/tracks/{track}', [TrackController::class, 'show'])->name('tracks.show');
Route::delete('/tracks/{track}', [TrackController::class, 'destroy'])->name('tracks.destroy');
Route::get('/tracks/{track}/status', [TrackController::class, 'status'])
    ->middleware(\App\Http\Middleware\JsonMiddleware::class)
    ->name('tracks.status');
Route::post('/tracks/{track}/start', [TrackController::class, 'start'])->name('tracks.start');
Route::post('/tracks/{track}/stop', [TrackController::class, 'stop'])->name('tracks.stop');
Route::post('/tracks/{track}/retry', [TrackController::class, 'retry'])->name('tracks.retry');
Route::post('/tracks/retry-all', [TrackController::class, 'retryAll'])->name('tracks.retry-all');
Route::post('/tracks/{track}/upload-to-youtube', [TrackController::class, 'uploadToYoutube'])->name('tracks.upload-to-youtube');
Route::post('/tracks/{track}/toggle-youtube-status', [TrackController::class, 'toggleYoutubeStatus'])
    ->middleware(\App\Http\Middleware\JsonMiddleware::class)
    ->name('tracks.toggle-youtube-status');

// Random track upload route
Route::get('/random-youtube-upload', [TrackController::class, 'randomYoutubeUpload'])->name('tracks.random-youtube-upload');

// Cron-friendly route for random YouTube uploads with API key protection
Route::get('/cron/youtube-upload/{apiKey}', function($apiKey) {
    // Check if the API key is valid (should match the one in environment variables)
    if ($apiKey !== env('CRON_API_KEY', 'changeme')) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid API key',
        ], 403);
    }
    
    // Just redirect to the random upload endpoint
    return redirect()->route('tracks.random-youtube-upload');
})->name('cron.youtube-upload');

// Genre routes
Route::resource('genres', GenreController::class);

// Direct Video Upload routes
Route::get('/videos/upload', [VideoUploadController::class, 'showUploadForm'])->name('videos.upload');
Route::post('/videos/upload', [VideoUploadController::class, 'uploadVideo'])->name('videos.upload.process');
Route::get('/videos/success', [VideoUploadController::class, 'showSuccessPage'])->name('videos.success');

// Test route for track operations
Route::get('/test-track-stop/{id}', function($id) {
    try {
        $track = \App\Models\Track::findOrFail($id);
        
        // Only stop if the track is in processing or pending state
        if (!in_array($track->status, ['processing', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'This track is not currently processing',
                'status' => $track->status,
            ], 422);
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
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => "Error: " . $e->getMessage(),
        ], 500);
    }
});

// YouTube routes
Route::prefix('youtube')->name('youtube.')->group(function () {
    Route::get('/status', [YouTubeController::class, 'status'])->name('status');
    Route::get('/upload', [YouTubeController::class, 'showUploadForm'])->name('upload.form');
    
    // Add route aliases to support multiple names for the same endpoint
    Route::post('/upload', [YouTubeController::class, 'uploadTrack'])->name('upload');
    Route::post('/upload-store', [YouTubeController::class, 'uploadTrack'])->name('upload.store');
    
    Route::get('/uploads', [YouTubeController::class, 'uploads'])->name('uploads');
    Route::post('/uploads/sync', [YouTubeController::class, 'syncUploads'])->name('sync');
    Route::post('/uploads/refresh-stats', [YouTubeController::class, 'refreshVideoStats'])->name('uploads.refresh-stats');
    Route::post('/refresh-stats', [YouTubeController::class, 'refreshVideoStats'])->name('refresh-stats');
    Route::post('/toggle-enabled', [YouTubeController::class, 'toggleYoutubeEnabled'])->name('toggle-enabled');
    
    // Video statistics routes
    Route::get('/video/{videoId}/stats', [YouTubeController::class, 'videoStats'])->name('video.stats');
    Route::post('/video/{videoId}/refresh-stats', [YouTubeController::class, 'refreshVideoStats'])->name('video.refresh-stats');
    
    // YouTube Bulk Upload routes
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::get('/', [YouTubeBulkController::class, 'index'])->name('index');
        Route::post('/queue', [YouTubeBulkController::class, 'queueUpload'])->name('queue');
        Route::post('/upload-now', [YouTubeBulkController::class, 'uploadNow'])->name('upload-now');
        Route::get('/queue-status', [YouTubeBulkController::class, 'queueStatus'])->name('queue-status');
        Route::post('/retry-failed', [YouTubeBulkController::class, 'retryFailed'])->name('retry-failed');
        Route::get('/eligible-tracks', [YouTubeBulkController::class, 'eligibleTracks'])->name('eligible-tracks');
    });
    
    // YouTube Auth routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/login', [\App\Http\Controllers\YouTubeAuthController::class, 'showLoginForm'])->name('login_form');
        Route::get('/redirect', [\App\Http\Controllers\YouTubeAuthController::class, 'redirect'])->name('redirect');
        Route::get('/status', [\App\Http\Controllers\YouTubeAuthController::class, 'status'])->name('status');
        
        // Account management routes
        Route::post('/set-active', [\App\Http\Controllers\YouTubeAuthController::class, 'setActiveAccount'])->name('set-active');
        Route::post('/delete-account', [\App\Http\Controllers\YouTubeAuthController::class, 'deleteAccount'])->name('delete-account');
    });
    
    // YouTube Analytics routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'index'])->name('index');
        Route::get('/summary', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'summary'])->name('summary');
        Route::get('/top-performing', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'topPerforming'])->name('top-performing');
        Route::post('/bulk-update', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'bulkUpdateAnalytics'])->name('bulk-update');
        Route::post('/update-all', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'bulkUpdateAnalytics'])->name('update-all');
        Route::get('/stale', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'staleAnalytics'])->name('stale');
        
        // Track-specific analytics
        Route::get('/tracks/{track}', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'trackAnalytics'])->name('track');
        Route::post('/tracks/{track}/update', [\App\Http\Controllers\YouTubeAnalyticsController::class, 'updateTrackAnalytics'])->name('track.update');
    });
});

// Simple route for YouTube reauthorization
Route::get('/youtube/auth', function() {
    return redirect()->route('youtube.auth.redirect');
})->name('youtube.reauth');

// Direct YouTube auth route
Route::get('/youtube-reauth', [\App\Http\Controllers\YouTubeAuthController::class, 'redirect'])->name('youtube.direct.reauth');

// Special route for YouTube OAuth callback
Route::get('/youtube-auth', [\App\Http\Controllers\YouTubeAuthController::class, 'callback'])->name('youtube.auth.callback');

// Settings routes
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/reset', [SettingsController::class, 'reset'])->name('settings.reset');

// Queue Management routes
Route::prefix('queue')->name('queue.')->group(function () {
    Route::get('/', [\App\Http\Controllers\QueueController::class, 'index'])->name('index');
    Route::get('/statistics', [\App\Http\Controllers\QueueController::class, 'statistics'])->name('statistics');
    Route::get('/health', [\App\Http\Controllers\QueueController::class, 'health'])->name('health');
    Route::get('/batches', [\App\Http\Controllers\QueueController::class, 'batches'])->name('batches');
    
    // Batch management
    Route::post('/batches/{batchId}/cancel', [\App\Http\Controllers\QueueController::class, 'cancelBatch'])->name('batches.cancel');
    Route::post('/batches/{batchId}/retry', [\App\Http\Controllers\QueueController::class, 'retryBatch'])->name('batches.retry');
    
    // Failed jobs management
    Route::post('/failed-jobs/clear', [\App\Http\Controllers\QueueController::class, 'clearFailedJobs'])->name('failed-jobs.clear');
    Route::post('/failed-jobs/retry', [\App\Http\Controllers\QueueController::class, 'retryFailedJobs'])->name('failed-jobs.retry');
    
    // Queue control
    Route::post('/pause/{queueName}', [\App\Http\Controllers\QueueController::class, 'pauseQueue'])->name('pause');
    Route::post('/resume/{queueName}', [\App\Http\Controllers\QueueController::class, 'resumeQueue'])->name('resume');
});

// Webhook routes (no CSRF protection needed)
Route::prefix('webhooks')->name('webhooks.')->withoutMiddleware(['web'])->group(function () {
    Route::post('/youtube', [WebhookController::class, 'youtube'])->name('youtube');
    Route::post('/suno', [WebhookController::class, 'suno'])->name('suno');
    Route::post('/generic/{service}', [WebhookController::class, 'generic'])->name('generic');
    Route::get('/status', [WebhookController::class, 'status'])->name('status');
});
