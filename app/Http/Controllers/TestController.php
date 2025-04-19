<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * The notification service instance.
     *
     * @var NotificationService
     */
    protected NotificationService $notificationService;

    /**
     * Create a new TestController instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a test page with notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function testNotification()
    {
        return view('test-notification');
    }

    /**
     * Set a flash message and redirect to test page.
     *
     * @param string $type
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setFlashMessage($type)
    {
        $messages = [
            'success' => 'Operation completed successfully!',
            'error' => 'An error occurred.',
            'warning' => 'Warning: This is a test warning.',
            'info' => 'This is an informational message.'
        ];

        if (isset($messages[$type])) {
            switch ($type) {
                case 'success':
                    $this->notificationService->success($messages[$type]);
                    break;
                case 'error':
                    $this->notificationService->error($messages[$type]);
                    break;
                case 'warning':
                    $this->notificationService->warning($messages[$type]);
                    break;
                case 'info':
                    $this->notificationService->info($messages[$type]);
                    break;
            }
        }

        return redirect()->route('test.notification');
    }
    
    /**
     * Test JSON response with notification.
     *
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function testJsonResponse($type)
    {
        $messages = [
            'success' => 'Operation completed successfully!',
            'error' => 'An error occurred.',
            'warning' => 'Warning: This is a test warning.',
            'info' => 'This is an informational message.'
        ];
        
        $message = $messages[$type] ?? 'Unknown notification type';
        
        return $this->notificationService->jsonResponse(
            $message,
            $type,
            $type === 'error' ? 400 : 200,
            ['timestamp' => now()->timestamp]
        );
    }
}
