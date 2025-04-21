<?php

namespace App\Livewire;

class Todo extends BaseComponent
{
    /**
     * The component's boot data for server-side rendering.
     */
    public function boot(): array
    {
        return [
            'placeholder' => 'Todo list is loading...'
        ];
    }
    
    /**
     * Render the component, ensuring it's rendered on the server.
     */
    public function render()
    {
        return $this->renderWithServerRendering(view('livewire.todo'));
    }
}
