<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ $genre->name }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div class="breadcrumbs text-sm">
                <ul>
                    <li><a href="{{ route('genres.index') }}">Genres</a></li> 
                    <li class="truncate" title="{{ $genre->name }}">{{ Str::limit($genre->name, 40) }}</li>
                </ul>
            </div>
            <div class="flex space-x-2">
                <x-button href="{{ route('genres.edit', $genre) }}" variant="outline" size="sm">
                    <x-icon name="pencil" size="4" class="mr-1" />
                    Edit Genre
                </x-button>
                <form action="{{ route('playlists.create-from-genre', $genre) }}" method="POST" class="inline-block">
                    @csrf
                    <x-button type="submit" variant="primary" size="sm">
                        <x-icon name="collection" size="4" class="mr-1" />
                        Create Playlist
                    </x-button>
                </form>
                <x-button 
                    href="{{ route('genres.index') }}" 
                    variant="ghost"
                    size="sm"
                >
                    <x-icon name="arrow-sm-left" size="4" class="mr-1" />
                    Back
                </x-button>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">Genre Info</h2>
                        <dl class="divide-y divide-base-200 text-sm">
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="font-medium text-base-content/70">Name</dt>
                                <dd class="text-base-content col-span-2">{{ $genre->name }}</dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="font-medium text-base-content/70">Slug</dt>
                                <dd class="text-base-content col-span-2">{{ $genre->slug }}</dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="font-medium text-base-content/70">Tracks</dt>
                                <dd class="text-base-content col-span-2">{{ $tracks->total() }}</dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="font-medium text-base-content/70">Description</dt>
                                <dd class="text-base-content col-span-2">{{ $genre->description ?: '-' }}</dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="font-medium text-base-content/70">Created</dt>
                                <dd class="text-base-content col-span-2" title="{{ $genre->created_at->format('Y-m-d H:i:s') }}">{{ $genre->created_at->diffForHumans() }}</dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="font-medium text-base-content/70">Updated</dt>
                                <dd class="text-base-content col-span-2" title="{{ $genre->updated_at->format('Y-m-d H:i:s') }}">{{ $genre->updated_at->diffForHumans() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">Tracks in {{ $genre->name }} ({{ $tracks->total() }})</h2>
                        
                        @if($tracks->isEmpty())
                            <div class="text-center py-10 text-base-content/70 italic">
                                No tracks found in this genre.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="table table-zebra table-sm w-full">
                                    <thead>
                                        <tr>
                                            <th wire:click="sortBy('title')" class="cursor-pointer">
                                                Title @if($sortField == 'title') <span class="ml-1">{{ $direction == 'asc' ? '↑' : '↓' }}</span> @endif
                                            </th>
                                            <th>Other Genres</th>
                                            <th wire:click="sortBy('duration')" class="cursor-pointer">
                                                Duration @if($sortField == 'duration') <span class="ml-1">{{ $direction == 'asc' ? '↑' : '↓' }}</span> @endif
                                            </th>
                                            <th wire:click="sortBy('created_at')" class="cursor-pointer">
                                                Added @if($sortField == 'created_at') <span class="ml-1">{{ $direction == 'asc' ? '↑' : '↓' }}</span> @endif
                                            </th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tracks as $track)
                                            <tr>
                                                <td>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="avatar">
                                                            <div class="mask mask-squircle w-10 h-10">
                                                                <img src="{{ $track->image_url }}" 
                                                                     alt="{{ $track->title }}" 
                                                                     onerror="this.src='{{ asset('images/no-image.jpg') }}'" />
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('tracks.show', $track) }}" class="font-bold hover:text-primary transition duration-150 ease-in-out">
                                                                {{ Str::limit($track->title, 40) }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($track->genres->where('id', '!=', $genre->id)->take(2) as $otherGenre)
                                                            <a href="{{ route('genres.show', $otherGenre) }}" class="badge badge-ghost badge-sm hover:bg-base-300">{{ $otherGenre->name }}</a>
                                                        @endforeach
                                                        @if($track->genres->count() > 3)
                                                            <div class="badge badge-ghost badge-sm">...</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    {{ formatDuration($track->duration_seconds ?: $track->duration) }}
                                                </td>
                                                <td>
                                                    <span title="{{ $track->created_at->format('Y-m-d H:i:s') }}">
                                                        {{ $track->created_at->diffForHumans(null, true) }} ago
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex items-center space-x-1">
                                                        <x-tooltip text="Play Track" position="top">
                                                            <x-button href="{{ route('tracks.play', $track) }}" variant="ghost" size="xs" icon>
                                                                <x-icon name="play" />
                                                            </x-button>
                                                        </x-tooltip>
                                                        <x-tooltip text="View Track Details" position="top">
                                                            <x-button href="{{ route('tracks.show', $track) }}" variant="ghost" size="xs" icon>
                                                                <x-icon name="eye" />
                                                            </x-button>
                                                        </x-tooltip>
                                                        <x-tooltip text="Edit Track" position="top">
                                                            <x-button href="{{ route('tracks.edit', $track) }}" variant="ghost" size="xs" icon>
                                                                <x-icon name="pencil" />
                                                            </x-button>
                                                        </x-tooltip>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-6">
                                {{ $tracks->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
