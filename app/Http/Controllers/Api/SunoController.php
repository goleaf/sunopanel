<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SunoClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SunoController extends Controller
{
    /**
     * Get tracks by style/genre
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTracksByStyle(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'style' => 'required|string',
            ]);
            
            $style = $request->input('style');
            $cookie = $request->input('cookie') ?? env('SUNO_COOKIE');
            
            if (empty($cookie)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Suno authentication cookie provided. Please provide a cookie parameter or set the SUNO_COOKIE environment variable.',
                ], 400);
            }
            
            $sunoClient = new SunoClient($cookie);
            $tracks = $sunoClient->getTracksByStyle($style);
            
            return response()->json([
                'success' => true,
                'style' => $style,
                'count' => count($tracks),
                'tracks' => $tracks,
            ]);
        } catch (Exception $e) {
            Log::error("Suno API error: {$e->getMessage()}");
            
            return response()->json([
                'success' => false,
                'message' => "Failed to fetch tracks: {$e->getMessage()}",
            ], 500);
        }
    }
} 