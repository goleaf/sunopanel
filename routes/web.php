<?php

use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VideoUploadController;
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
Route::get('/tracks/{track}/status', [TrackController::class, 'status'])->name('tracks.status');
Route::post('/tracks/{track}/retry', [TrackController::class, 'retry'])->name('tracks.retry');
Route::post('/tracks/retry-all', [TrackController::class, 'retryAll'])->name('tracks.retry-all');
Route::post('/tracks/{track}/upload-to-youtube', [TrackController::class, 'uploadToYoutube'])->name('tracks.upload-to-youtube');

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

// YouTube Integration
Route::prefix('youtube')->name('youtube.')->group(function () {
    // Authentication
    Route::get('/auth', [App\Http\Controllers\Api\YouTubeAuthController::class, 'index'])->name('auth');
    Route::get('/connect', [App\Http\Controllers\Api\YouTubeAuthController::class, 'redirectToProvider'])->name('auth.redirect');
    Route::get('/callback', [App\Http\Controllers\Api\YouTubeAuthController::class, 'handleProviderCallback'])->name('auth.callback');
    Route::post('/toggle-oauth', [App\Http\Controllers\Api\YouTubeAuthController::class, 'toggleOAuth'])->name('toggle.oauth');
    Route::post('/toggle-simple', [App\Http\Controllers\Api\YouTubeAuthController::class, 'toggleSimple'])->name('toggle.simple');
    
    // Add missing status route
    Route::get('/status', [App\Http\Controllers\Api\YouTubeAuthController::class, 'status'])->name('status');
    
    // Simple YouTube Uploader Login
    Route::get('/login', [App\Http\Controllers\Api\YouTubeAuthController::class, 'showLoginForm'])->name('auth.login_form');
    Route::post('/login', [App\Http\Controllers\Api\YouTubeAuthController::class, 'saveCredentials'])->name('auth.save_credentials');
    
    // Configuration Instructions
    Route::get('/config', [App\Http\Controllers\YouTubeDiagnosticsController::class, 'showConfigInstructions'])->name('config');
    
    // Upload
    Route::get('/upload', [App\Http\Controllers\YouTubeUploadController::class, 'showUploadForm'])->name('upload.form');
    Route::post('/upload', [App\Http\Controllers\YouTubeUploadController::class, 'uploadTrack'])->name('upload.process');
    Route::get('/uploads', [App\Http\Controllers\YouTubeUploadController::class, 'viewUploads'])->name('uploads');
    Route::post('/tracks/{id}/upload', [App\Http\Controllers\YouTubeUploadController::class, 'uploadTrackDirect'])->name('upload.direct');
    
    // Diagnostics
    Route::get('/diagnostics', [App\Http\Controllers\YouTubeDiagnosticsController::class, 'index'])->name('diagnostics');
    Route::post('/diagnostics/test-upload', [App\Http\Controllers\YouTubeDiagnosticsController::class, 'testUpload'])->name('diagnostics.test-upload');
});

// Special route for YouTube OAuth callback to match the redirect URI in Google Console
Route::get('/youtube-auth', [App\Http\Controllers\Api\YouTubeAuthController::class, 'handleProviderCallback'])->name('youtube.auth.external_callback');
