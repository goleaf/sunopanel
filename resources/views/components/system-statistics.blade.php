@props(['stats'])

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <x-card shadow="md" rounded="md" class="bg-gradient-to-br from-indigo-50 to-white">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                <x-icon name="music-note" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Tracks</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['tracksCount']) }}</p>
            </div>
        </div>
    </x-card>

    <x-card shadow="md" rounded="md" class="bg-gradient-to-br from-green-50 to-white">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <x-icon name="tag" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Genres</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['genresCount']) }}</p>
            </div>
        </div>
    </x-card>

    <x-card shadow="md" rounded="md" class="bg-gradient-to-br from-purple-50 to-white">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <x-icon name="collection" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Playlists</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['playlistsCount']) }}</p>
            </div>
        </div>
    </x-card>

    <x-card shadow="md" rounded="md" class="bg-gradient-to-br from-blue-50 to-white">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <x-icon name="clock" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Duration</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $stats['totalDuration'] ?? '0:00' }}</p>
            </div>
        </div>
    </x-card>
</div> 