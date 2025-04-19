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
                    <x-table.responsive-table :columns="[
                        'Title' => __('Title'),
                        'Artist' => __('Artist'),
                        'Genre' => __('Genre'),
                        'Duration' => __('Duration'),
                        'Created At' => __('Created At'),
                        'Actions' => __('Actions')
                    ]" compact="true">
                        @forelse($tracks as $track)
                            <x-table.responsive-row>
                                <x-table.responsive-cell label="{{ __('Title') }}">
                                    <a href="{{ route('tracks.show', $track) }}" class="font-bold text-primary hover:underline">
                                        {{ $track->title }}
                                    </a>
                                </x-table.responsive-cell>
                                <x-table.responsive-cell label="{{ __('Artist') }}">
                                    {{ $track->artist }}
                                </x-table.responsive-cell>
                                <x-table.responsive-cell label="{{ __('Genre') }}">
                                    @if($track->genre)
                                        <a href="{{ route('genres.show', $track->genre) }}" class="badge badge-accent">
                                            {{ $track->genre->name }}
                                        </a>
                                    @else
                                        <span class="opacity-50">-</span>
                                    @endif
                                </x-table.responsive-cell>
                                <x-table.responsive-cell label="{{ __('Duration') }}">
                                    {{ formatDuration($track->duration) }}
                                </x-table.responsive-cell>
                                <x-table.responsive-cell label="{{ __('Created At') }}">
                                    {{ $track->created_at->format('Y-m-d') }}
                                </x-table.responsive-cell>
                                <x-table.responsive-cell label="{{ __('Actions') }}">
                                    <div class="flex flex-col md:flex-row gap-2 action-buttons">
                                        <x-button href="{{ route('tracks.show', $track) }}" color="info" size="xs">
                                            <x-icon name="eye" size="4" class="mr-1" />
                                            View
                                        </x-button>
                                        
                                        <x-button href="{{ route('tracks.edit', $track) }}" color="warning" size="xs">
                                            <x-icon name="pencil" size="4" class="mr-1" />
                                            Edit
                                        </x-button>
                                        
                                        <form method="POST" action="{{ route('tracks.destroy', $track) }}" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" color="error" size="xs" 
                                                onclick="return confirm('{{ __('Are you sure you want to delete this track?') }}')">
                                                <x-icon name="trash" size="4" class="mr-1" />
                                                Delete
                                            </x-button>
                                        </form>
                                    </div>
                                </x-table.responsive-cell>
                            </x-table.responsive-row>
                        @empty
                            <x-table.responsive-row>
                                <x-table.responsive-cell label="" colspan="6" class="text-center py-4">
                                    <div class="text-base-content/60">{{ __('No tracks found') }}</div>
                                </x-table.responsive-cell>
                            </x-table.responsive-row>
                        @endforelse
                    </x-table.responsive-table>
                </div>

                <div class="mt-4">
                    {{ $tracks->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
