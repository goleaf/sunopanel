<?php

use App\Http\Controllers\Api\V1\TrackController as V1TrackController;
use App\Http\Controllers\Api\V1\YouTubeController as V1YouTubeController;
use App\Http\Middleware\ApiRateLimitMiddleware;
use App\Http\Middleware\JsonMiddleware;
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

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is healthy',
        'version' => 'v1',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
    ]);
})->middleware([JsonMiddleware::class, ApiRateLimitMiddleware::class . ':default']);

// API Version 1 Routes
Route::prefix('v1')->middleware([JsonMiddleware::class])->group(function () {
    
    // Track API Routes
    Route::prefix('tracks')->middleware(ApiRateLimitMiddleware::class . ':tracks')->group(function () {
        // CRUD operations
        Route::get('/', [V1TrackController::class, 'index']);
        Route::get('/{track}', [V1TrackController::class, 'show']);
        
        // Track status and control
        Route::get('/{track}/status', [V1TrackController::class, 'status']);
        Route::post('/{track}/start', [V1TrackController::class, 'start']);
        Route::post('/{trackId}/stop', [V1TrackController::class, 'stop']);
        Route::post('/{track}/retry', [V1TrackController::class, 'retry']);
        
        // Bulk operations with stricter rate limiting
        Route::middleware(ApiRateLimitMiddleware::class . ':bulk')->group(function () {
            Route::post('/bulk/status', [V1TrackController::class, 'bulkStatus']);
            Route::post('/bulk/action', [V1TrackController::class, 'bulkAction']);
        });
    });

    // YouTube API Routes
    Route::prefix('youtube')->middleware(ApiRateLimitMiddleware::class . ':youtube')->group(function () {
        // Account and statistics
        Route::get('/account', [V1YouTubeController::class, 'accountInfo']);
        Route::get('/statistics', [V1YouTubeController::class, 'statistics']);
        
        // Single track operations
        Route::post('/upload/{track}', [V1YouTubeController::class, 'upload'])
            ->middleware(ApiRateLimitMiddleware::class . ':upload');
        Route::get('/status/{track}', [V1YouTubeController::class, 'status']);
        
        // Bulk operations with stricter rate limiting
        Route::middleware(ApiRateLimitMiddleware::class . ':bulk')->group(function () {
            Route::post('/bulk/upload', [V1YouTubeController::class, 'bulkUpload']);
            Route::get('/bulk/queue-status', [V1YouTubeController::class, 'queueStatus']);
            Route::post('/bulk/retry-failed', [V1YouTubeController::class, 'retryFailed']);
        });
    });
});

// Legacy API Routes (for backward compatibility)
// These will be deprecated in future versions
Route::prefix('tracks')->middleware([
    JsonMiddleware::class, 
    ApiRateLimitMiddleware::class . ':tracks'
])->group(function () {
    // Legacy track operations - redirect to v1
    Route::get('/{track}/status', function (Request $request, $track) {
        return redirect()->route('api.v1.tracks.status', ['track' => $track]);
    });
    
    Route::post('/{track}/start', function (Request $request, $track) {
        return app(V1TrackController::class)->start(
            \App\Models\Track::findOrFail($track),
            $request
        );
    });
    
    Route::post('/{trackId}/stop', function (Request $request, $trackId) {
        return app(V1TrackController::class)->stop($trackId);
    });
    
    Route::post('/{track}/retry', function (Request $request, $track) {
        return app(V1TrackController::class)->retry(
            \App\Models\Track::findOrFail($track)
        );
    });
    
    // Legacy bulk operations
    Route::middleware(ApiRateLimitMiddleware::class . ':bulk')->group(function () {
        Route::post('/status-bulk', function (Request $request) {
            return app(V1TrackController::class)->bulkStatus($request);
        });
        
        Route::post('/bulk-action', function (Request $request) {
            return app(V1TrackController::class)->bulkAction($request);
        });
    });
});

// Rate Limit Status Endpoint (for debugging)
Route::get('/rate-limit-status', function (Request $request) {
    $statuses = [];
    $types = ['default', 'tracks', 'youtube', 'bulk', 'upload'];
    
    foreach ($types as $type) {
        $statuses[$type] = ApiRateLimitMiddleware::getRateLimitStatus($request, $type);
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Rate limit status retrieved',
        'data' => $statuses,
        'timestamp' => now()->toISOString(),
    ]);
})->middleware([JsonMiddleware::class, ApiRateLimitMiddleware::class . ':default']);

// API Documentation endpoint
Route::get('/docs', function () {
    return response()->json([
        'success' => true,
        'message' => 'SunoPanel API Documentation',
        'data' => [
            'version' => 'v1',
            'base_url' => url('/api/v1'),
            'endpoints' => [
                'tracks' => [
                    'GET /tracks' => 'List all tracks with pagination and filtering',
                    'GET /tracks/{id}' => 'Get specific track details',
                    'GET /tracks/{id}/status' => 'Get track processing status',
                    'POST /tracks/{id}/start' => 'Start track processing',
                    'POST /tracks/{id}/stop' => 'Stop track processing',
                    'POST /tracks/{id}/retry' => 'Retry track processing',
                    'POST /tracks/bulk/status' => 'Get bulk track status',
                    'POST /tracks/bulk/action' => 'Perform bulk actions on tracks',
                ],
                'youtube' => [
                    'GET /youtube/account' => 'Get YouTube account information',
                    'GET /youtube/statistics' => 'Get upload statistics',
                    'POST /youtube/upload/{id}' => 'Upload track to YouTube',
                    'GET /youtube/status/{id}' => 'Get YouTube upload status',
                    'POST /youtube/bulk/upload' => 'Bulk upload tracks to YouTube',
                    'GET /youtube/bulk/queue-status' => 'Get bulk upload queue status',
                    'POST /youtube/bulk/retry-failed' => 'Retry failed YouTube uploads',
                ],
            ],
            'rate_limits' => [
                'default' => '60 requests per minute',
                'tracks' => '100 requests per minute',
                'youtube' => '30 requests per minute',
                'bulk' => '10 requests per minute',
                'upload' => '5 requests per minute',
            ],
            'response_format' => [
                'success' => true,
                'message' => 'Response message',
                'data' => 'Response data',
                'timestamp' => 'ISO 8601 timestamp',
            ],
        ],
        'timestamp' => now()->toISOString(),
    ]);
})->middleware([JsonMiddleware::class, ApiRateLimitMiddleware::class . ':default']);