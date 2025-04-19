<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Services\Logging\LoggingService;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    private UserService $userService;

    private LoggingService $loggingService;

    public function __construct(UserService $userService, LoggingService $loggingService)
    {
        $this->userService = $userService;
        $this->loggingService = $loggingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        try {
            $this->loggingService->info('Users index accessed', [
                'search' => $request->input('search', ''),
                'sort' => $request->input('sort', 'id'),
                'order' => $request->input('order', 'asc'),
            ]);

            $search = $request->input('search', '');
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'asc');

            // Get paginated users from service
            $perPage = (int) $request->input('per_page', 10);
            $users = $this->userService->getAll($perPage);

            // Apply search if provided
            if (! empty($search)) {
                $this->loggingService->info('User search applied', ['term' => $search]);

                $users = User::where(function ($q) use ($search) {
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
            $this->loggingService->logError($e, $request, 'UserController@index');

            return view('users.index', [
                'users' => collect(),
                'headers' => [
                    'id' => ['label' => 'ID', 'sortable' => true],
                    'name' => ['label' => 'Name', 'sortable' => true],
                    'email' => ['label' => 'Email', 'sortable' => true],
                    'actions' => ['label' => 'Actions', 'sortable' => false],
                ],
                'error' => 'An error occurred while retrieving users.',
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->loggingService->info('User create form accessed');

        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        try {
            $this->loggingService->info('User store method called', ['request' => $request->except(['password', 'password_confirmation'])]);

            // Delegate user creation to service
            $user = $this->userService->store($request);

            $this->loggingService->info('User created successfully', ['user_id' => $user->id]);

            return redirect()->route('users.index')
                ->with('success', 'User created successfully!');
        } catch (Exception $e) {
            $this->loggingService->logError($e, $request, 'UserController@store');

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
            $this->loggingService->info('User show accessed', ['user_id' => $user->id]);

            // Get user with their playlists
            $user = $this->userService->getUserWithPlaylists($user);

            return view('users.show', compact('user'));
        } catch (Exception $e) {
            $this->loggingService->logError($e, request(), 'UserController@show', $user->id);

            return view('users.show', [
                'user' => $user,
                'error' => 'An error occurred while retrieving user data.',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $this->loggingService->info('User edit form accessed', ['user_id' => $user->id]);

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        try {
            $this->loggingService->info('User update method called', [
                'user_id' => $user->id,
                'request' => $request->except(['password', 'password_confirmation']),
            ]);

            // Delegate user update to service
            $this->userService->update($request, $user);

            $this->loggingService->info('User updated successfully', ['user_id' => $user->id]);

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully!');
        } catch (Exception $e) {
            $this->loggingService->logError($e, $request, 'UserController@update', $user->id);

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
            $this->loggingService->info('User delete initiated', ['user_id' => $user->id]);

            // Delegate user deletion to service
            $this->userService->delete($user);

            $this->loggingService->info('User deleted successfully', ['user_id' => $user->id]);

            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully!');
        } catch (Exception $e) {
            $this->loggingService->logError($e, request(), 'UserController@destroy', $user->id);

            return redirect()->back()
                ->with('error', 'An error occurred while deleting the user. Please try again.');
        }
    }
}
