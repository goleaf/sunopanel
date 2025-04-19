<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

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
     * Get paginated users with optional searching and sorting.
     */
    public function getPaginatedUsers(Request $request): LengthAwarePaginator
    {
        $query = User::query()->withCount('playlists');

        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
            Log::info('User search applied in service', ['term' => $search]);
        }

        // Apply sorting
        $sortField = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc'); // Changed from 'order' to 'direction'

        // Validate sort field
        $allowedSortFields = ['id', 'name', 'email', 'created_at', 'playlists_count']; // Add playlists_count if needed
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'id';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        $query->orderBy($sortField, $direction);
        Log::info('User sort applied in service', ['field' => $sortField, 'direction' => $direction]);

        // Paginate
        $perPage = (int) $request->input('per_page', 15); // Default per page
        $users = $query->paginate($perPage)->withQueryString(); // Append query string parameters

        Log::info('Retrieved paginated users', [
            'count' => $users->total(),
            'per_page' => $perPage,
            'current_page' => $users->currentPage(),
        ]);

        return $users;
    }

    /**
     * Get all users with pagination (Original method - kept for reference/potential different use case)
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        $users = User::withCount('playlists')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        Log::info('Retrieved all users (using getAll method)', [
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
    private function storeAvatar(UploadedFile $file, int $userId): string
    {
        $filename = 'user_'.$userId.'_'.time().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('avatars', $filename, 'public');

        return $path;
    }
}
