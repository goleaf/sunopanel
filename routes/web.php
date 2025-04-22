<?php

use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VideoUploadController;
use App\Http\Controllers\YouTubeController;
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
    // Main routes
    Route::get('/', [YouTubeController::class, 'index'])->name('index');
    Route::get('/status', [YouTubeController::class, 'status'])->name('status');
    
    // Authentication
    Route::get('/auth', [YouTubeController::class, 'redirectToProvider'])->name('auth');
    Route::get('/auth/redirect', [YouTubeController::class, 'redirectToProvider'])->name('auth.redirect');
    Route::post('/credentials', [YouTubeController::class, 'saveCredentials'])->name('save.credentials');
    Route::get('/login', [YouTubeController::class, 'showLoginForm'])->name('auth.login_form');
    
    // Upload
    Route::get('/upload', [YouTubeController::class, 'showUploadForm'])->name('upload.form');
    Route::post('/upload', [YouTubeController::class, 'uploadTrack'])->name('upload.process');
    Route::get('/uploads', [YouTubeController::class, 'uploads'])->name('uploads');
    
    // Test
    Route::post('/test', [YouTubeController::class, 'testUpload'])->name('test');
});

// Special route for YouTube OAuth callback
Route::get('/youtube-auth', [YouTubeController::class, 'handleCallback'])->name('youtube.auth.callback');
