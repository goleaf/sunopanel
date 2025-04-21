<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Livewire\Attributes\Title;
use App\Livewire\BaseComponent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class Dashboard extends BaseComponent
{
    /**
     * The component's initial data.
     * This is used for SSR to hydrate the component on the server.
     *
     * @return array
     */
    public function boot(): array
    {
        // Return any data needed for initial server-side rendering
        return [
            'placeholder' => 'Dashboard data is loading...'
        ];
    }

    /**
     * Set the page title and specify the layout
     */
    #[Title('Dashboard')]
    #[Layout('layouts.app')]
    public function render()
    {
        return $this->renderWithServerRendering(view('livewire.dashboard'));
    }
} 