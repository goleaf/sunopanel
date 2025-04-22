<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SunoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SunoController extends Controller
{
    /**
     * @var SunoService
     */
    protected SunoService $sunoService;

    /**
     * Constructor
     *
     * @param SunoService $sunoService
     */
    public function __construct(SunoService $sunoService)
    {
        $this->sunoService = $sunoService;
    }

    /**
     * Get tracks by music style
     *
     * @param Request $request
     * @param string $style The music style to fetch tracks for
     * @return JsonResponse
     */
    public function getTracksByStyle(Request $request, string $style): JsonResponse
    {
        try {
            $tracks = $this->sunoService->getTracksByStyle($style);
            
            return response()->json([
                'success' => true,
                'style' => $style,
                'tracks' => $tracks,
                'count' => count($tracks),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in SunoController::getTracksByStyle', [
                'style' => $style,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'style' => $style,
                'message' => 'Failed to fetch tracks: ' . $e->getMessage(),
            ], 500);
        }
    }
} 