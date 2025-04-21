<?php

namespace App\Traits;

trait ServerRenderable
{
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;

    /**
     * Persist the component's state to server-side storage.
     *
     * @var bool
     */
    protected bool $persistState = true;
    
    /**
     * Force rendering on the server.
     *
     * @param \Illuminate\View\View $view
     * @return \Illuminate\View\View
     */
    public function renderWithServerRendering($view)
    {
        if (method_exists($view, 'renderOnServer')) {
            return $view->renderOnServer();
        }
        
        return $view;
    }
    
    /**
     * The component's initial data.
     * This is used for SSR to hydrate the component on the server.
     *
     * @return array
     */
    public function boot(): array
    {
        // Return any data needed for initial server-side rendering
        // Override this method in your component for specific data
        return [];
    }
} 