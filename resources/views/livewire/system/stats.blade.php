<div>
    <div class="pb-6">
        <h2 class="text-2xl font-bold">System Statistics</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Tracks Card -->
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-600 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Tracks</p>
                    <p class="text-2xl font-semibold text-gray-700">{{ $trackCount }}</p>
                </div>
            </div>
        </div>

        <!-- Genres Card -->
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-600 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Genres</p>
                    <p class="text-2xl font-semibold text-gray-700">{{ $genreCount }}</p>
                </div>
            </div>
        </div>

        <!-- Playlists Card -->
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-500 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Playlists</p>
                    <p class="text-2xl font-semibold text-gray-700">{{ $playlistCount }}</p>
                </div>
            </div>
        </div>

        <!-- Duration Card -->
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-500 bg-opacity-75 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Total Duration</p>
                    <p class="text-2xl font-semibold text-gray-700">{{ $totalDuration }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Storage Usage Card -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h3 class="text-xl font-semibold mb-4">Storage Usage</h3>
        <div class="relative pt-1">
            @php
                $percentage = $storageUsageMB > 0 ? min(($storageUsageMB / 1000) * 100, 100) : 0;
            @endphp
            <div class="overflow-hidden h-4 mb-4 text-xs flex rounded bg-gray-200">
                <div style="width:{{ $percentage }}%" 
                     class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $percentage > 90 ? 'bg-red-500' : ($percentage > 70 ? 'bg-yellow-500' : 'bg-green-500') }}">
                </div>
            </div>
            <div class="flex justify-between text-sm">
                <span>{{ $storageUsageMB }} MB used</span>
                <span>1000 MB limit</span>
            </div>
        </div>
    </div>
</div> 