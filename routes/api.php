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
    
    // Retry processing a track
    Route::post('/{track}/retry', [TrackController::class, 'retry']);
    
    // Retry all failed tracks
    Route::post('/retry-all', [TrackController::class, 'retryAll']);
    
    // Bulk status check (for optimizing multiple status checks)
    Route::post('/status-bulk', [TrackController::class, 'statusBulk']);
}); 