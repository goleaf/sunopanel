<?php

namespace App\Livewire;

use App\Traits\ServerRenderable;
use Livewire\Component;

abstract class BaseComponent extends Component
{
    use ServerRenderable;
    
    /**
     * Early returns are used to exit from a method before reaching its end.
     * This helps to skip unnecessary code execution in certain conditions.
     *
     * @var bool
     */
    protected bool $earlyReturns = true;

    /**
     * Whether this component should use keep-alive.
     * Keep-alive maintains the component's state between page loads.
     *
     * @var bool
     */
    protected bool $keepAlive = true;
    
    /**
     * Indicates if the component should be rendered on the server.
     * This helps with SEO and initial page load performance.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
    /**
     * Render the component with server-side rendering.
     * Override the Component render method to enforce rendering on the server.
     *
     * @return mixed
     */
    abstract public function render();
} 