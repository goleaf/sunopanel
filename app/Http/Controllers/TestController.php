<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
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
            session()->flash($type, $messages[$type]);
        }

        return redirect()->route('test.notification');
    }
}
