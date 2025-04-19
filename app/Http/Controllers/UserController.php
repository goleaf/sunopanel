<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\User\UserService;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;

final class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        try {
            $search = $request->input('search', '');
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'asc');
            
            // Get paginated users from service
            $perPage = (int) $request->input('per_page', 10);
            $users = $this->userService->getAll($perPage);
            
            // Apply search if provided
            if (!empty($search)) {
                $users = User::where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy($sort, $order)
                ->paginate($perPage)
                ->withQueryString();
            }
            
            // Define headers for the data table
            $headers = [
                'id' => ['label' => 'ID', 'sortable' => true],
                'name' => ['label' => 'Name', 'sortable' => true],
                'email' => ['label' => 'Email', 'sortable' => true],
                'actions' => ['label' => 'Actions', 'sortable' => false],
            ];
            
            return view('users.index', compact('users', 'headers', 'search', 'sort', 'order'));
        } catch (Exception $e) {
            Log::error('Error retrieving users list: ' . $e->getMessage(), [
                'exception' => $e,
                'search' => $request->input('search', ''),
                'sort' => $request->input('sort', 'id'),
                'order' => $request->input('order', 'asc')
            ]);
            
            return view('users.index', [
                'users' => collect(),
                'headers' => [
                    'id' => ['label' => 'ID', 'sortable' => true],
                    'name' => ['label' => 'Name', 'sortable' => true],
                    'email' => ['label' => 'Email', 'sortable' => true],
                    'actions' => ['label' => 'Actions', 'sortable' => false],
                ],
                'error' => 'An error occurred while retrieving users.'
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        try {
            // Delegate user creation to service
            $this->userService->store($request);
            
            return redirect()->route('users.index')
                ->with('success', 'User created successfully!');
        } catch (Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->except(['password', 'password_confirmation'])
            ]);
            
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'An error occurred while creating the user. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        try {
            // Get user with their playlists
            $user = $this->userService->getUserWithPlaylists($user);
            return view('users.show', compact('user'));
        } catch (Exception $e) {
            Log::error('Error showing user: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id
            ]);
            
            return view('users.show', [
                'user' => $user,
                'error' => 'An error occurred while retrieving user data.'
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        try {
            // Delegate user update to service
            $this->userService->update($request, $user);
            
            return redirect()->route('users.index')
                ->with('success', 'User updated successfully!');
        } catch (Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
                'request' => $request->except(['password', 'password_confirmation'])
            ]);
            
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'An error occurred while updating the user. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            // Delegate user deletion to service
            $this->userService->delete($user);
            
            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully!');
        } catch (Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the user. Please try again.');
        }
    }
} 