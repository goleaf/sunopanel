<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

trait WithNotifications
{
    /**
     * Flash a success message to the session.
     *
     * @param string $message
     * @return void
     */
    public function notifySuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    /**
     * Flash an error message to the session.
     *
     * @param string $message
     * @return void
     */
    public function notifyError(string $message): void
    {
        Session::flash('error', $message);
    }

    /**
     * Flash a warning message to the session.
     *
     * @param string $message
     * @return void
     */
    public function notifyWarning(string $message): void
    {
        Session::flash('warning', $message);
    }

    /**
     * Flash an info message to the session.
     *
     * @param string $message
     * @return void
     */
    public function notifyInfo(string $message): void
    {
        Session::flash('info', $message);
    }

    /**
     * Return a JsonResponse with proper formatting.
     *
     * @param string $message
     * @param string $type
     * @param int $statusCode
     * @param array $data
     * @return JsonResponse
     */
    public function jsonResponse(string $message, string $type = 'success', int $statusCode = 200, array $data = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'type' => $type,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Get all session notifications for JavaScript.
     *
     * @return array
     */
    public function getNotificationsForJs(): array
    {
        $notifications = [];
        
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if (Session::has($type)) {
                $notifications[] = [
                    'type' => $type,
                    'message' => Session::get($type)
                ];
            }
        }
        
        return $notifications;
    }
} 