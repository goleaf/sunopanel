<?php

namespace App\Http\Controllers;

use App\Services\SunoClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SunoCommandController extends Controller
{
    /**
     * Get tracks by style with a single CLI-friendly command
     * 
     * @param string|null $cookie
     * @param string $style
     * @return JsonResponse
     */
    public function __invoke(?string $cookie = null, string $style = ''): JsonResponse
    {
        try {
            // Use provided cookie or environment variable
            $cookieValue = $cookie ?? env('SUNO_COOKIE');
            
            // If style is empty, return an error
            if (empty($style)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Style parameter is required. Example: dark trap metalcore',
                ], 400);
            }
            
            // Check if cookie is provided
            if (empty($cookieValue)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Suno authentication cookie provided. Please provide a cookie parameter or set the SUNO_COOKIE environment variable.',
                ], 400);
            }
            
            // Create Suno client and fetch tracks
            $sunoClient = new SunoClient($cookieValue);
            $tracks = $sunoClient->getTracksByStyle($style);
            
            return response()->json([
                'success' => true,
                'style' => $style,
                'count' => count($tracks),
                'tracks' => $tracks,
            ]);
        } catch (Exception $e) {
            Log::error("Suno command error: {$e->getMessage()}");
            
            return response()->json([
                'success' => false,
                'message' => "Failed to fetch tracks: {$e->getMessage()}",
            ], 500);
        }
    }
} 