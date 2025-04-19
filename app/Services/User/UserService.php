<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final readonly class UserService
{
    /**
     * Store a new user
     */
    public function store(UserStoreRequest $request): User
    {
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'] ?? 'user',
        ]);

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $path = $this->storeAvatar($request->file('avatar'), $user->id);
            $user->update(['avatar' => $path]);
        }

        Log::info('User created successfully', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return $user;
    }

    /**
     * Update an existing user
     */
    public function update(UserUpdateRequest $request, User $user): User
    {
        $validatedData = $request->validated();

        $userData = [
            'name' => $validatedData['name'] ?? $user->name,
            'email' => $validatedData['email'] ?? $user->email,
            'role' => $validatedData['role'] ?? $user->role,
        ];

        // Update password if provided
        if (isset($validatedData['password'])) {
            $userData['password'] = Hash::make($validatedData['password']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $this->storeAvatar($request->file('avatar'), $user->id);
            $userData['avatar'] = $path;
        }

        $user->update($userData);

        Log::info('User updated successfully', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * Delete a user
     */
    public function delete(User $user): bool
    {
        // Check if user has playlists
        $playlistsCount = $user->playlists()->count();

        // Delete avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $deleted = $user->delete();

        Log::info('User deleted', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'playlists_count' => $playlistsCount,
            'success' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Get all users with pagination
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        $users = User::withCount('playlists')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        Log::info('Retrieved all users', [
            'count' => $users->total(),
        ]);

        return $users;
    }

    /**
     * Get a user with their playlists eager loaded
     */
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

    /**
     * Store avatar image
     */
    private function storeAvatar($file, int $userId): string
    {
        $filename = 'user_'.$userId.'_'.time().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('avatars', $filename, 'public');

        return $path;
    }
}
