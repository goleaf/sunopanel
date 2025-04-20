<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserForm extends Component
{
    use WithFileUploads;

    public ?User $user = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'user';
    public $avatar;
    public ?string $currentAvatar = null;
    public bool $isEditMode = false;

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $this->isEditMode
                    ? Rule::unique('users')->ignore($this->user->id)
                    : Rule::unique('users'),
            ],
            'role' => ['required', 'string', 'in:user,admin'],
            'avatar' => ['nullable', 'image', 'max:1024'], // 1MB max
        ];

        if (!$this->isEditMode || !empty($this->password)) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }
    
    public function mount(?User $user = null): void
    {
        $this->isEditMode = $user !== null;
        
        if ($this->isEditMode && $user) {
            $this->user = $user;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role;
            $this->currentAvatar = $user->avatar;
        }
    }

    public function render()
    {
        return view('livewire.user-form');
    }
    
    public function save(): void
    {
        $this->validate();
        
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
        
        // Only set password if provided (required for new users)
        if (!empty($this->password)) {
            $userData['password'] = Hash::make($this->password);
        }
        
        try {
            if ($this->isEditMode) {
                // Update existing user
                $this->user->update($userData);
                $user = $this->user;
                $message = "User {$this->name} updated successfully";
            } else {
                // Create new user
                $user = User::create($userData);
                $message = "User {$this->name} created successfully";
            }
            
            // Handle avatar upload
            if ($this->avatar) {
                // Delete old avatar if exists
                if ($this->isEditMode && $this->currentAvatar) {
                    Storage::disk('public')->delete($this->currentAvatar);
                }
                
                $filename = 'user_' . $user->id . '_' . time() . '.' . $this->avatar->getClientOriginalExtension();
                $path = $this->avatar->storeAs('avatars', $filename, 'public');
                $user->update(['avatar' => $path]);
            }
            
            Log::info($this->isEditMode ? 'User updated' : 'User created', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            
            session()->flash('success', $message);
            
            // Redirect to users list
            $this->redirect(route('users.index'));
        } catch (\Exception $e) {
            Log::error('User ' . ($this->isEditMode ? 'update' : 'creation') . ' failed', [
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Error ' . ($this->isEditMode ? 'updating' : 'creating') . ' user: ' . $e->getMessage());
        }
    }
    
    public function removeAvatar(): void
    {
        if ($this->isEditMode && $this->currentAvatar) {
            Storage::disk('public')->delete($this->currentAvatar);
            $this->user->update(['avatar' => null]);
            $this->currentAvatar = null;
            
            session()->flash('success', 'Avatar removed successfully');
        }
    }
} 