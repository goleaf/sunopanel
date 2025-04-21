<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;

class Alert extends Component
{
    public string $type = 'info';
    public string $message = '';
    public bool $dismissible = false;

    public function mount(string $type = 'info', string $message = '', bool $dismissible = false)
    {
        $this->type = $type;
        $this->message = $message;
        $this->dismissible = $dismissible;
    }

    public function dismiss()
    {
        $this->message = '';
        $this->emit('alertDismissed');
    }

    public function render()
    {
        return view('livewire.components.alert');
    }
} 