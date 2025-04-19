<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Notification Service
 * 
 * Provides helper methods for working with notifications across the application.
 */
class NotificationService
{
    /**
     * Flash a success notification.
     * 
     * @param string $message The notification message
     * @return void
     */
    public function success(string $message): void
    {
        Session::flash('success', $message);
    }
    
    /**
     * Flash an error notification.
     * 
     * @param string $message The notification message
     * @return void
     */
    public function error(string $message): void
    {
        Session::flash('error', $message);
    }
    
    /**
     * Flash a warning notification.
     * 
     * @param string $message The notification message
     * @return void
     */
    public function warning(string $message): void
    {
        Session::flash('warning', $message);
    }
    
    /**
     * Flash an info notification.
     * 
     * @param string $message The notification message
     * @return void
     */
    public function info(string $message): void
    {
        Session::flash('info', $message);
    }
    
    /**
     * Get JSON response with a notification.
     * 
     * @param string $message The notification message
     * @param string $type The notification type (success, error, warning, info)
     * @param int $status HTTP status code
     * @param array $data Additional data to include in the response
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonResponse(string $message, string $type = 'success', int $status = 200, array $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $message,
            'type' => $type,
            'data' => $data,
        ], $status);
    }
    
    /**
     * Add notifications to JavaScript window object.
     * 
     * @param Request $request
     * @return array
     */
    public function getNotificationsForJs(Request $request): array
    {
        $notifications = [];
        
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if ($request->session()->has($type)) {
                $notifications[] = [
                    'type' => $type,
                    'message' => $request->session()->get($type)
                ];
            }
        }
        
        return $notifications;
    }
} 