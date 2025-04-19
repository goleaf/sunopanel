<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Tracks') }}
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
                        <form action="{{ route('tracks.index') }}" method="GET" class="join">
                            <input type="text" name="search" placeholder="Search tracks..." value="{{ request('search') }}" class="input input-bordered join-item w-full" />
                            <select name="genre" class="select select-bordered join-item">
                                <option value="">All Genres</option>
                                @foreach($genres as $genre)
                                    <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                                        {{ $genre->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-button type="submit" color="primary" class="join-item">
                                <x-icon name="search" size="5" class="mr-1" />
                                Search
                            </x-button>
                        </form>
                    </div>
                    <div class="flex space-x-2">
                        <x-button href="{{ route('tracks.create') }}" color="primary">
                            <x-icon name="plus" size="5" class="mr-1" />
                            Add New Track
                        </x-button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <x-table adaptiveLayout="true" tableClasses="tracks-table">
                        <x-slot name="header">
                            <x-table.heading>Title</x-table.heading>
                            <x-table.heading>Genres</x-table.heading>
                            <x-table.heading>Duration</x-table.heading>
                            <x-table.heading>Added</x-table.heading>
                            <x-table.heading>Actions</x-table.heading>
                        </x-slot>
                        
                        <x-slot name="body">
                            @forelse($tracks as $track)
                                <x-table.row>
                                    <x-table.cell label="Title">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 mr-2">
                                                <img class="h-10 w-10 rounded-md object-cover" 
                                                    src="{{ $track->image_url }}" 
                                                    alt="{{ $track->title }}" 
                                                    onerror="this.src='{{ asset('images/no-image.jpg') }}'" 
                                                />
                                            </div>
                                            <div>
                                                <a href="{{ route('tracks.show', $track->id) }}" class="text-primary-600 hover:text-primary-900">
                                                    {{ $track->title }}
                                                </a>
                                            </div>
                                        </div>
                                    </x-table.cell>
                                    <x-table.cell label="Genres">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($track->genres as $genre)
                                                <x-badge>{{ $genre->name }}</x-badge>
                                            @endforeach
                                        </div>
                                    </x-table.cell>
                                    <x-table.cell label="Duration">
                                        {{ formatDuration($track->duration_seconds ?: $track->duration) }}
                                    </x-table.cell>
                                    <x-table.cell label="Added">
                                        {{ $track->created_at->diffForHumans() }}
                                    </x-table.cell>
                                    <x-table.cell label="Actions">
                                        <div class="flex items-center space-x-1">
                                            <x-button href="{{ route('tracks.edit', $track->id) }}" size="xs" color="secondary" icon title="Edit">
                                                <x-icon name="pencil" />
                                            </x-button>
                                            
                                            <a href="{{ $track->audio_url }}" target="_blank" class="btn btn-xs btn-ghost" title="Open in new tab">
                                                <x-icon name="external-link" />
                                            </a>
                                            
                                            <x-button href="{{ route('tracks.play', $track->id) }}" size="xs" color="success" icon title="Play">
                                                <x-icon name="play" />
                                            </x-button>
                                            
                                            <form action="{{ route('tracks.destroy', $track->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" size="xs" color="error" icon title="Delete">
                                                    <x-icon name="trash" />
                                                </x-button>
                                            </form>
                                        </div>
                                    </x-table.cell>
                                </x-table.row>
                            @empty
                                <x-table.row>
                                    <x-table.cell colspan="5" class="text-center py-6">
                                        <div class="text-gray-500">No tracks found</div>
                                        <div class="mt-2">
                                            <x-button href="{{ route('tracks.create') }}" color="primary" size="sm">
                                                <x-icon name="plus" class="mr-1" /> Add Track
                                            </x-button>
                                        </div>
                                    </x-table.cell>
                                </x-table.row>
                            @endforelse
                        </x-slot>
                    </x-table>
                </div>

                <div class="mt-4">
                    {{ $tracks->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
