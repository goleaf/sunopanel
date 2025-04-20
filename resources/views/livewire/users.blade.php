<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Users</h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>
            Create User
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-base-100 shadow-sm rounded-lg overflow-hidden mb-6">
        <div class="flex justify-between items-center p-4 border-b border-base-300">
            <div class="flex-1">
                <input 
                    wire:model.debounce.300ms="search" 
                    type="text" 
                    placeholder="Search users..."
                    class="input input-bordered w-full max-w-xs" 
                />
            </div>
            <div>
                <select wire:model="perPage" class="select select-bordered">
                    <option value="10">10 per page</option>
                    <option value="15">15 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                </select>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th wire:click="sortBy('{{ $header['key'] }}')" class="cursor-pointer">
                                {{ $header['label'] }}
                                @if($sort === $header['key'])
                                    <i class="fas fa-sort-{{ $direction === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="hover">
                            <td>{{ $user->id }}</td>
                            <td>
                                <div class="flex items-center space-x-3">
                                    @if($user->avatar)
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-12 h-12">
                                                <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" />
                                            </div>
                                        </div>
                                    @else
                                        <div class="avatar placeholder">
                                            <div class="bg-neutral text-neutral-content mask mask-squircle w-12 h-12">
                                                <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold">{{ $user->name }}</div>
                                        <div class="text-sm opacity-50">{{ $user->role }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <div class="flex space-x-2">
                                    <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button wire:click="confirmDelete({{ $user->id }})" class="btn btn-sm btn-outline btn-error">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) }}" class="text-center py-4">
                                No users found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-4 py-3 border-t border-base-300">
            {{ $users->links() }}
        </div>
    </div>
    
    @if($userToDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-base-100 p-6 rounded-lg shadow-lg max-w-md w-full">
                <h3 class="text-lg font-bold mb-4">Confirm Delete</h3>
                <p class="mb-6">Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelDelete" class="btn btn-outline">Cancel</button>
                    <button wire:click="deleteUser" class="btn btn-error">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div> 