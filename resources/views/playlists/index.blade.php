<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Playlists') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4">
        @if (session('success'))
            <div class="alert alert-success mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="card bg-base-100 shadow-md">
            <div class="card-body">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div class="w-full md:w-2/3">
                        <form action="{{ route('playlists.index') }}" method="GET" class="join">
                            <input type="text" name="search" placeholder="Search playlists..." value="{{ request('search') }}" class="input input-bordered join-item w-full" />
                            <x-button type="submit" color="primary" class="join-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </x-button>
                        </form>
                    </div>
                    <div>
                        <x-button href="{{ route('playlists.create') }}" color="primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create New Playlist
                        </x-button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>
                                    <a href="{{ route('playlists.index', array_merge(request()->query(), [
                                        'sort' => 'title',
                                        'direction' => request('sort') === 'title' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        Title
                                        @if(request('sort') === 'title')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                @if(request('direction') === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                @endif
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th>Description</th>
                                <th>
                                    <a href="{{ route('playlists.index', array_merge(request()->query(), [
                                        'sort' => 'genre_id',
                                        'direction' => request('sort') === 'genre_id' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        Genre
                                        @if(request('sort') === 'genre_id')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                @if(request('direction') === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                @endif
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ route('playlists.index', array_merge(request()->query(), [
                                        'sort' => 'tracks_count',
                                        'direction' => request('sort') === 'tracks_count' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        Tracks
                                        @if(request('sort') === 'tracks_count')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                @if(request('direction') === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                @endif
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ route('playlists.index', array_merge(request()->query(), [
                                        'sort' => 'created_at',
                                        'direction' => request('sort') === 'created_at' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        Created
                                        @if(request('sort') === 'created_at')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                @if(request('direction') === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                @endif
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($playlists as $playlist)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if($playlist->cover_image)
                                                <div class="avatar">
                                                    <div class="mask mask-squircle w-12 h-12">
                                                        <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->title }}" />
                                                    </div>
                                                </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('playlists.show', $playlist) }}" class="font-bold text-primary hover:underline">
                                                    {{ $playlist->title }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="truncate max-w-xs">
                                            {{ Str::limit($playlist->description, 50) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($playlist->genre)
                                            <a href="{{ route('genres.show', $playlist->genre) }}" class="badge badge-accent">
                                                {{ $playlist->genre->name }}
                                            </a>
                                        @else
                                            <span class="opacity-50">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="badge">{{ $playlist->tracks_count }}</div>
                                    </td>
                                    <td>
                                        {{ $playlist->created_at->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        <div class="flex flex-col md:flex-row gap-2">
                                            <x-button href="{{ route('playlists.show', $playlist) }}" color="info" size="xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View
                                            </x-button>
                                            <x-button href="{{ route('playlists.edit', $playlist) }}" color="warning" size="xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edit
                                            </x-button>
                                            <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" color="error" size="xs" onclick="return confirm('Are you sure you want to delete this playlist?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete
                                                </x-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-base-content/60">
                                            No playlists found. 
                                            <a href="{{ route('playlists.create') }}" class="link link-primary">
                                                Create your first playlist
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $playlists->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
