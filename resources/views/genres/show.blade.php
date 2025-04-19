<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ $genre->name }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-primary">{{ $genre->name }}</h1>
            <div class="flex gap-2">
                <x-button href="{{ route('genres.edit', $genre->id) }}" color="primary" size="sm">
                    <x-icon name="pencil" size="5" class="mr-1" />
                    Edit Genre
                </x-button>
                
                <form action="{{ route('playlists.create-from-genre', $genre->id) }}" method="POST" class="inline-block">
                    @csrf
                    <x-button type="submit" color="success" size="sm">
                        <x-icon name="music" size="5" class="mr-1" />
                        Create Playlist
                    </x-button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                <x-icon name="check" size="6" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error mb-4">
                <x-icon name="x" size="6" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title border-b pb-4 mb-4">Tracks in this Genre</h2>
                
                @if($tracks->isEmpty())
                    <div class="text-base-content/60">No tracks in this genre yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>
                                        <div class="flex items-center">
                                            Track
                                            <a href="{{ route('genres.show', ['genre' => $genre->id, 'sort' => 'name', 'order' => ($sortField == 'name' && $sortOrder == 'asc') ? 'desc' : 'asc']) }}">
                                                <x-icon name="{{ ($sortField == 'name' && $sortOrder == 'asc') ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
                                            </a>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="flex items-center">
                                            Duration
                                            <a href="{{ route('genres.show', ['genre' => $genre->id, 'sort' => 'duration', 'order' => ($sortField == 'duration' && $sortOrder == 'asc') ? 'desc' : 'asc']) }}">
                                                <x-icon name="{{ ($sortField == 'duration' && $sortOrder == 'asc') ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
                                            </a>
                                        </div>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tracks as $track)
                                    <tr>
                                        <td>
                                            <div class="avatar">
                                                <div class="mask mask-squircle w-10 h-10">
                                                    <img src="{{ $track->image_url }}" alt="{{ $track->title }}">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('tracks.show', $track) }}" class="font-bold text-primary hover:underline">{{ $track->title }}</a>
                                        </td>
                                        <td>{{ $track->duration ?? '0:00' }}</td>
                                        <td>
                                            <div class="flex gap-2 justify-end">
                                                <x-button href="{{ route('tracks.show', $track) }}" color="ghost" size="sm" icon>
                                                    <x-icon name="eye" size="5" />
                                                </x-button>
                                                <x-button href="{{ route('tracks.play', $track) }}" color="success" size="sm" icon>
                                                    <x-icon name="play" size="5" />
                                                </x-button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $tracks->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title border-b pb-4 mb-4">Genre Information</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <tbody>
                            <tr>
                                <td class="font-medium w-40">Name:</td>
                                <td>{{ $genre->name }}</td>
                            </tr>
                            <tr>
                                <td class="font-medium">Slug:</td>
                                <td>{{ $genre->slug }}</td>
                            </tr>
                            <tr>
                                <td class="font-medium">Track Count:</td>
                                <td>{{ $tracks->total() }}</td>
                            </tr>
                            <tr>
                                <td class="font-medium">Created:</td>
                                <td>{{ $genre->created_at->format('M d, Y') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <x-button href="{{ route('genres.index') }}" color="ghost">
                <x-icon name="arrow-left" size="5" class="mr-2" />
                Back to Genres
            </x-button>
        </div>
    </div>
</x-app-layout>
