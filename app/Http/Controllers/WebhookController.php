<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Events\WebhookReceived;
use App\Services\WebhookService;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {}

    /**
     * Handle YouTube webhook notifications.
     */
    public function youtube(Request $request): JsonResponse
    {
        try {
            // Validate webhook signature if configured
            if (!$this->webhookService->validateYouTubeSignature($request)) {
                Log::warning('Invalid YouTube webhook signature', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();
            
            Log::info('YouTube webhook received', [
                'payload' => $payload,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Process the webhook
            $result = $this->webhookService->processYouTubeWebhook($payload);

            // Dispatch event for other parts of the application
            Event::dispatch(new WebhookReceived('youtube', $payload, $result));

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'processed' => $result['processed'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('YouTube webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Suno AI webhook notifications.
     */
    public function suno(Request $request): JsonResponse
    {
        try {
            // Validate webhook signature if configured
            if (!$this->webhookService->validateSunoSignature($request)) {
                Log::warning('Invalid Suno webhook signature', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();
            
            Log::info('Suno webhook received', [
                'payload' => $payload,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Process the webhook
            $result = $this->webhookService->processSunoWebhook($payload);

            // Dispatch event for other parts of the application
            Event::dispatch(new WebhookReceived('suno', $payload, $result));

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'processed' => $result['processed'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Suno webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle generic webhook notifications.
     */
    public function generic(Request $request, string $service): JsonResponse
    {
        try {
            // Validate webhook signature if configured
            if (!$this->webhookService->validateGenericSignature($request, $service)) {
                Log::warning('Invalid webhook signature', [
                    'service' => $service,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();
            
            Log::info('Generic webhook received', [
                'service' => $service,
                'payload' => $payload,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Process the webhook
            $result = $this->webhookService->processGenericWebhook($service, $payload);

            // Dispatch event for other parts of the application
            Event::dispatch(new WebhookReceived($service, $payload, $result));

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'service' => $service,
                'processed' => $result['processed'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Generic webhook processing failed', [
                'service' => $service,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'service' => $service,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get webhook status and statistics.
     */
    public function status(): JsonResponse
    {
        try {
            $stats = $this->webhookService->getWebhookStats();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get webhook stats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get webhook stats',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
} 