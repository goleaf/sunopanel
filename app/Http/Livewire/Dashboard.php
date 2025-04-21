<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;

class Dashboard extends Component
{
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;

    /**
     * Set the page title
     */
    #[Title('Dashboard')]
    public function render()
    {
        return view('livewire.dashboard');
    }
} 