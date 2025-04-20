<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class UserShow extends Component
{
    public User $user;
    
    public function mount(User $user): void
    {
        $this->user = $this->getUserWithPlaylists($user);
    }

    public function render()
    {
        return view('livewire.user-show');
    }
    
    public function getUserWithPlaylists(User $user): User
    {
        // Eager load playlists with their tracks count and genre
        return $user->load([
            'playlists' => function ($query) {
                $query->withCount('tracks');
            },
            'playlists.genre',
        ]);
    }
    
    public function refresh(): void
    {
        $this->user = $this->getUserWithPlaylists($this->user->fresh());
        
        Log::info('User data refreshed', [
            'user_id' => $this->user->id,
        ]);
        
        session()->flash('info', 'User data refreshed');
    }
} 