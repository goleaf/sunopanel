<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-base-content">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-base-100 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Tracks Card -->
                    <div class="bg-base-100 overflow-hidden shadow rounded-lg">
                        <div class="p-5 bg-primary">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-focus rounded-md p-3">
                                    <svg class="h-6 w-6 text-primary-content" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-primary-content opacity-80 truncate">
                                            Total Tracks
                                        </dt>
                                        <dd>
                                            <div class="text-lg font-medium text-primary-content">
                                                {{ $tracksCount }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-base-200 px-5 py-3">
                            <div class="text-sm">
                                <a href="{{ route('tracks.index') }}" class="font-medium text-primary hover:text-primary-focus">
                                    View all tracks
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Genres Card -->
                    <div class="bg-base-100 overflow-hidden shadow rounded-lg">
                        <div class="p-5 bg-secondary">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-secondary-focus rounded-md p-3">
                                    <svg class="h-6 w-6 text-secondary-content" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-secondary-content opacity-80 truncate">
                                            Total Genres
                                        </dt>
                                        <dd>
                                            <div class="text-lg font-medium text-secondary-content">
                                                {{ $genresCount }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-base-200 px-5 py-3">
                            <div class="text-sm">
                                <a href="{{ route('genres.index') }}" class="font-medium text-secondary hover:text-secondary-focus">
                                    View all genres
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Playlists Card -->
                    <div class="bg-base-100 overflow-hidden shadow rounded-lg">
                        <div class="p-5 bg-accent">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-accent-focus rounded-md p-3">
                                    <svg class="h-6 w-6 text-accent-content" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-accent-content opacity-80 truncate">
                                            Total Playlists
                                        </dt>
                                        <dd>
                                            <div class="text-lg font-medium text-accent-content">
                                                {{ $playlistsCount }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-base-200 px-5 py-3">
                            <div class="text-sm">
                                <a href="{{ route('playlists.index') }}" class="font-medium text-accent hover:text-accent-focus">
                                    View all playlists
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Tracks -->
                <div class="mt-8">
                    <h3 class="text-lg leading-6 font-medium text-base-content mb-4">
                        Recently Added Tracks
                    </h3>
                    
                    <x-table.table>
                        <x-slot name="header">
                            <x-table.header-cell>Title</x-table.header-cell>
                            <x-table.header-cell>Genres</x-table.header-cell>
                            <x-table.header-cell>Added</x-table.header-cell>
                            <x-table.header-cell>Audio</x-table.header-cell>
                        </x-slot>

                        <x-slot name="body">
                            @forelse ($recentTracks as $track)
                                <tr>
                                    <x-table.cell>
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <img class="h-10 w-10 rounded object-cover" src="{{ $track->image_url }}" alt="{{ $track->title }}">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-base-content">
                                                    {{ $track->title }}
                                                </div>
                                            </div>
                                        </div>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <div class="flex flex-wrap">
                                            @foreach($track->genres as $genre)
                                                <span class="px-2 py-1 m-1 text-xs rounded-full bg-primary-content/20 text-primary">
                                                    {{ $genre->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </x-table.cell>
                                    <x-table.cell>
                                        {{ $track->created_at->format('Y-m-d') }}
                                    </x-table.cell>
                                    <x-table.cell>
                                        <audio controls class="w-40">
                                            <source src="{{ $track->audio_url }}" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                    </x-table.cell>
                                </tr>
                            @empty
                                <tr>
                                    <x-table.cell colspan="4" align="center">
                                        <div class="text-base-content opacity-70">No tracks found</div>
                                    </x-table.cell>
                                </tr>
                            @endforelse
                        </x-slot>
                    </x-table.table>

                    <div class="mt-4 text-right">
                        <a href="{{ route('tracks.index') }}" class="btn btn-primary btn-sm">
                            View All Tracks
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 