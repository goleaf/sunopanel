<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">User Profile</h1>
        <div class="space-x-2">
            <button wire:click="refresh" class="btn btn-outline btn-sm">
                <i class="fas fa-sync-alt mr-2"></i> Refresh
            </button>
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
        </div>
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

    @if(session('info'))
        <div class="alert alert-info mb-4">
            {{ session('info') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Info Card -->
        <div class="bg-base-100 shadow-sm rounded-lg overflow-hidden col-span-1">
            <div class="p-6">
                <div class="flex flex-col items-center text-center mb-6">
                    @if($user->avatar)
                        <div class="avatar mb-4">
                            <div class="w-24 h-24 rounded-full">
                                <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" />
                            </div>
                        </div>
                    @else
                        <div class="avatar placeholder mb-4">
                            <div class="bg-neutral text-neutral-content rounded-full w-24 h-24">
                                <span class="text-2xl">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                            </div>
                        </div>
                    @endif
                    <h2 class="text-xl font-bold">{{ $user->name }}</h2>
                    <p class="badge badge-outline mt-1">{{ ucfirst($user->role) }}</p>
                </div>

                <div class="divider"></div>

                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-gray-500">Email</div>
                        <div>{{ $user->email }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Joined</div>
                        <div>{{ $user->created_at->format('F j, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Last Updated</div>
                        <div>{{ $user->updated_at->format('F j, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Playlists</div>
                        <div>{{ $user->playlists_count ?? $user->playlists->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Playlists -->
        <div class="bg-base-100 shadow-sm rounded-lg overflow-hidden col-span-1 lg:col-span-2">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">Playlists</h2>
                
                @if($user->playlists->isEmpty())
                    <div class="alert alert-info">
                        This user has not created any playlists yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Genre</th>
                                    <th>Tracks</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->playlists as $playlist)
                                    <tr class="hover">
                                        <td>{{ $playlist->title }}</td>
                                        <td>
                                            @if($playlist->genre)
                                                <span class="badge">{{ $playlist->genre->name }}</span>
                                            @else
                                                <span class="badge badge-ghost">None</span>
                                            @endif
                                        </td>
                                        <td>{{ $playlist->tracks_count ?? 0 }}</td>
                                        <td>{{ $playlist->created_at->format('M j, Y') }}</td>
                                        <td>
                                            <div class="flex space-x-1">
                                                <a href="{{ route('playlists.show', $playlist->id) }}" class="btn btn-xs btn-ghost">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div> 