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
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>
                                    <a href="{{ route('tracks.index', array_merge(request()->query(), [
                                        'sort' => 'title',
                                        'direction' => request('sort') === 'title' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        Title
                                        @if(request('sort') === 'title')
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
                                        @endif
                                    </a>
                                </th>
                                <th>Genres</th>
                                <th>
                                    <a href="{{ route('tracks.index', array_merge(request()->query(), [
                                        'sort' => 'created_at',
                                        'direction' => request('sort') === 'created_at' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        Added
                                        @if(request('sort') === 'created_at')
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
                                        @endif
                                    </a>
                                </th>
                                <th>Audio</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tracks as $track)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar">
                                                <div class="mask mask-squircle w-12 h-12">
                                                    <img src="{{ $track->image_url }}" alt="{{ $track->title }}" />
                                                </div>
                                            </div>
                                            <div>
                                                <a href="{{ route('tracks.show', $track) }}" class="font-bold text-primary hover:underline">
                                                    {{ $track->title }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($track->genres as $genre)
                                                <a href="{{ route('genres.show', $genre) }}" class="badge badge-primary">
                                                    {{ $genre->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        {{ $track->created_at->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        <x-audio-player 
                                            src="{{ $track->audio_url }}" 
                                            showDownload="false"
                                        />
                                    </td>
                                    <td>
                                        <x-action-buttons
                                            :view="route('tracks.show', $track)"
                                            :edit="route('tracks.edit', $track)"
                                            :delete="route('tracks.destroy', $track)"
                                            confirmMessage="Are you sure you want to delete this track?"
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-base-content/60">No tracks found</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tracks->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
