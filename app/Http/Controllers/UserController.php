<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;

final class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        try {
            $search = $request->input('search', '');
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'asc');
            
            $query = User::query();
            
            // Search functionality
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            // Sorting
            $query->orderBy($sort, $order);
            
            // Paginate the results
            $users = $query->paginate(10)->withQueryString();
            
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
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            
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
        return view('users.show', compact('user'));
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
    public function update(Request $request, User $user): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'password' => 'nullable|string|min:8|confirmed',
            ]);
            
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];
            
            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }
            
            $user->update($userData);
            
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
            $user->delete();
            
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