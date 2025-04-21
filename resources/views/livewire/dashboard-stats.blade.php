<div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Tracks Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-600 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 dark:text-gray-400">Tracks</p>
                    <p class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ $trackCount }}</p>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('tracks.index') }}" class="text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:underline">View all tracks</a>
            </div>
        </div>

        <!-- Genres Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-600 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 dark:text-gray-400">Genres</p>
                    <p class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ $genreCount }}</p>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('genres.index') }}" class="text-green-600 dark:text-green-400 text-sm font-medium hover:underline">View all genres</a>
            </div>
        </div>

        <!-- Playlists Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-500 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 dark:text-gray-400">Playlists</p>
                    <p class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ $playlistCount }}</p>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('playlists.index') }}" class="text-yellow-600 dark:text-yellow-400 text-sm font-medium hover:underline">View all playlists</a>
            </div>
        </div>

        <!-- Duration Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-500 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 dark:text-gray-400">Total Duration</p>
                    <p class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ $totalDuration }}</p>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-gray-500 dark:text-gray-400 text-sm">Total playback time</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Storage Usage Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Storage Usage</h3>
            <div class="relative pt-1">
                @php
                    $percentage = $storageUsageMB > 0 ? min(($storageUsageMB / 1000) * 100, 100) : 0;
                    $barColor = $percentage > 90 ? 'bg-red-500' : ($percentage > 70 ? 'bg-yellow-500' : 'bg-green-500');
                @endphp
                <div class="overflow-hidden h-6 mb-4 text-xs flex rounded-full bg-gray-200 dark:bg-gray-700">
                    <div style="width:{{ $percentage }}%" 
                        class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $barColor }}">
                        {{ round($percentage) }}%
                    </div>
                </div>
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ $storageUsageMB }} MB used</span>
                    <span>1000 MB limit</span>
                </div>
            </div>
        </div>

        <!-- Recent Tracks Activity -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Recent Tracks</h3>
            @if (count($recentTracks) > 0)
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($recentTracks as $track)
                        <li class="py-3">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $track['title'] }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                        {{ $track['artist'] }}
                                    </p>
                                </div>
                                <div>
                                    <a href="{{ route('tracks.show', $track['id']) }}" class="inline-flex items-center shadow-sm px-2.5 py-0.5 border border-gray-300 dark:border-gray-700 text-sm leading-5 font-medium rounded-full text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        View
                                    </a>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Added {{ $track['created_at'] }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">
                    <a href="{{ route('tracks.index') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">
                        View all tracks
                        <span aria-hidden="true"> &rarr;</span>
                    </a>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">No tracks added yet.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Popular Genres -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Popular Genres</h3>
            @if (count($popularGenres) > 0)
                <ul class="space-y-3">
                    @foreach ($popularGenres as $genre)
                        <li>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">{{ $genre['name'] }}</span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                    {{ $genre['track_count'] }} tracks
                                </span>
                            </div>
                            <div class="mt-1 relative pt-1">
                                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                                    @php
                                        $maxTracks = max(array_column($popularGenres, 'track_count'));
                                        $percentage = $maxTracks > 0 ? ($genre['track_count'] / $maxTracks) * 100 : 0;
                                    @endphp
                                    <div style="width:{{ $percentage }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">
                    <a href="{{ route('genres.index') }}" class="text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-500 dark:hover:text-green-300">
                        View all genres
                        <span aria-hidden="true"> &rarr;</span>
                    </a>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">No genres created yet.</p>
            @endif
        </div>

        <!-- Tracks Added Chart -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Tracks Added (Last 7 Days)</h3>
            <div class="flex items-center justify-between space-x-4 pt-4">
                @foreach ($tracksByDay as $date => $data)
                    <div class="flex flex-col items-center">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $data['label'] }}</div>
                        <div class="relative mt-2">
                            @php
                                $maxCount = max(array_column($tracksByDay, 'count'));
                                $height = $maxCount > 0 ? ($data['count'] / $maxCount) * 100 : 0;
                                $height = max($height, 10); // Minimum bar height for visibility
                            @endphp
                            <div class="flex flex-col items-center justify-end">
                                <div class="absolute bottom-0 text-xs text-gray-700 dark:text-gray-300 -mt-5">{{ $data['count'] }}</div>
                                <div class="bg-blue-500 rounded-t-sm w-6" style="height: {{ $height }}px; margin-top: 20px;"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 mb-8">
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('tracks.create') }}" class="btn btn-primary w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Track
                </a>
                <a href="{{ route('playlists.create') }}" class="btn btn-secondary w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Playlist
                </a>
                <a href="{{ route('genres.create') }}" class="btn btn-accent w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Genre
                </a>
            </div>
        </div>
    </div>
</div> 