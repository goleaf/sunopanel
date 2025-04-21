<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;
use Livewire\Exceptions\BypassViewHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class LivewireExceptionHandler
{
    /**
     * Handle a Livewire exception with custom rendering.
     *
     * @param  \Throwable  $exception
     * @param  string  $component
     * @param  string  $method
     * @return mixed
     */
    public function handle(Throwable $exception, $component, $method)
    {
        // Log the exception
        Log::error('Livewire Exception', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'component' => $component,
            'method' => $method,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        // In production, return a user-friendly error
        if (app()->environment('production')) {
            // Return a simple error message for SSR to use
            if (request()->isXmlHttpRequest()) {
                throw new HttpException(500, 'An error occurred while processing your request.');
            }
            
            // For SSR, provide a user-friendly error view
            throw new BypassViewHandler(
                view('errors.livewire', [
                    'component' => $component,
                    'message' => 'An error occurred while loading this content.',
                ])->render()
            );
        }

        // In non-production environments, let the exception bubble up
        // so it can be handled by Laravel's exception handler
        return false;
    }
} 