<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class Users extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $sort = 'id';
    public string $direction = 'asc';
    public int $perPage = 15;
    
    // For delete confirmation
    public ?int $userToDelete = null;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'sort' => ['except' => 'id'],
        'direction' => ['except' => 'asc'],
        'perPage' => ['except' => 15],
    ];

    public function render()
    {
        $users = $this->getPaginatedUsers();
        
        $headers = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'actions', 'label' => 'Actions'],
        ];
        
        return view('livewire.users', [
            'users' => $users,
            'headers' => $headers,
        ]);
    }
    
    public function getPaginatedUsers()
    {
        $query = User::query()->withCount('playlists');

        // Apply search if provided
        if (!empty($this->search)) {
            $query->where(function (Builder $q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
            Log::info('User search applied', ['term' => $this->search]);
        }

        // Validate sort field
        $allowedSortFields = ['id', 'name', 'email', 'created_at', 'playlists_count'];
        $this->sort = in_array($this->sort, $allowedSortFields) ? $this->sort : 'id';
        $this->direction = in_array($this->direction, ['asc', 'desc']) ? $this->direction : 'asc';

        $query->orderBy($this->sort, $this->direction);

        return $query->paginate($this->perPage);
    }
    
    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }
    
    public function confirmDelete(int $userId): void
    {
        $this->userToDelete = $userId;
    }
    
    public function deleteUser(): void
    {
        if (!$this->userToDelete) {
            return;
        }
        
        $user = User::find($this->userToDelete);
        
        if (!$user) {
            session()->flash('error', 'User not found.');
            $this->userToDelete = null;
            return;
        }
        
        // Delete avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        $username = $user->name;
        $deleted = $user->delete();
        
        if ($deleted) {
            session()->flash('success', "User {$username} deleted successfully.");
            Log::info('User deleted', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        } else {
            session()->flash('error', "Failed to delete user {$username}.");
        }
        
        $this->userToDelete = null;
    }
    
    public function cancelDelete(): void
    {
        $this->userToDelete = null;
    }
} 