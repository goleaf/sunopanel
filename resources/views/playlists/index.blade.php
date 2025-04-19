<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Playlists') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4">
        @if (session('success'))
            <div class="alert alert-success mb-4">
                <x-icon name="check" size="6" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error mb-4">
                <x-icon name="x" size="6" />
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
                                <x-icon name="search" size="5" />
                            </x-button>
                        </form>
                    </div>
                    <div>
                        <x-button href="{{ route('playlists.create') }}" color="primary">
                            <x-icon name="plus" size="5" class="mr-2" />
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
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
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
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
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
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
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
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
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
                                                <x-icon name="eye" size="4" class="mr-1" />
                                                View
                                            </x-button>
                                            <x-button href="{{ route('playlists.edit', $playlist) }}" color="warning" size="xs">
                                                <x-icon name="pencil" size="4" class="mr-1" />
                                                Edit
                                            </x-button>
                                            <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" color="error" size="xs" onclick="return confirm('Are you sure you want to delete this playlist?')">
                                                    <x-icon name="trash" size="4" class="mr-1" />
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
