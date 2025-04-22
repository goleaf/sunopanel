<?php

use App\Http\Controllers\Api\TrackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Track API Routes
Route::prefix('tracks')->group(function() {
    // Get track status
    Route::get('/{track}/status', [TrackController::class, 'status']);
    
    // Start processing a track
    Route::post('/{track}/start', [TrackController::class, 'start']);
    
    // Stop processing a track
    Route::post('/{trackId}/stop', [TrackController::class, 'stop']);
    
    // Retry processing a track
    Route::post('/{track}/retry', [TrackController::class, 'retry']);
    
    // Bulk operations
    Route::post('/start-all', [TrackController::class, 'startAll']);
    Route::post('/stop-all', [TrackController::class, 'stopAll']);
    Route::post('/retry-all', [TrackController::class, 'retryAll']);
    Route::post('/status-bulk', [TrackController::class, 'statusBulk']);
    Route::post('/bulk-action', [TrackController::class, 'bulkAction']);
}); 

// YouTube routes
Route::prefix('youtube')->group(function () {
    Route::post('/upload/{id}', [App\Http\Controllers\Api\YouTubeController::class, 'uploadVideo']);
    Route::post('/upload-all', [App\Http\Controllers\Api\YouTubeController::class, 'uploadAll']);
});

// YouTube upload routes
Route::post('/youtube/upload/{id}', [App\Http\Controllers\Api\YouTubeController::class, 'uploadVideo']);
Route::post('/youtube/upload-all', [App\Http\Controllers\Api\YouTubeController::class, 'uploadAll']);

Route::middleware('auth:sanctum')->group(function () {
    // YouTube upload routes
    Route::prefix('youtube')->group(function () {
        Route::get('/status', [App\Http\Controllers\Api\YouTubeUploadController::class, 'getUploadStatus']);
        Route::post('/upload/track/{trackId}', [App\Http\Controllers\Api\YouTubeUploadController::class, 'uploadTrack']);
        Route::post('/upload/all', [App\Http\Controllers\Api\YouTubeUploadController::class, 'uploadAllTracks']);
    });
}); 