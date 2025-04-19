<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="text-2xl font-semibold mb-6">Batch Operations</h1>
                    
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold mb-4">Tracks</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-800 border dark:border-gray-700">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 border-b dark:border-gray-700 text-left">Title</th>
                                        <th class="py-2 px-4 border-b dark:border-gray-700 text-left">Genres</th>
                                        <th class="py-2 px-4 border-b dark:border-gray-700 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($tracks as $track)
                                        <tr>
                                            <td class="py-2 px-4 border-b dark:border-gray-700">{{ $track->title }}</td>
                                            <td class="py-2 px-4 border-b dark:border-gray-700">
                                                @foreach ($track->genres as $genre)
                                                    <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded px-2 py-1 text-xs mr-1 mb-1">
                                                        {{ $genre->name }}
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td class="py-2 px-4 border-b dark:border-gray-700">
                                                <a href="{{ route('tracks.show', $track) }}" class="text-blue-600 dark:text-blue-400 hover:underline mr-2">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-4 px-4 text-center border-b dark:border-gray-700">No tracks found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold mb-4">Genres</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @forelse ($genres as $genre)
                                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                    <h3 class="font-semibold">{{ $genre->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $genre->tracks_count ?? 0 }} tracks</p>
                                    <a href="{{ route('genres.show', $genre) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">View</a>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-4">No genres found</div>
                            @endforelse
                        </div>
                    </div>
                    
                    <div>
                        <h2 class="text-xl font-semibold mb-4">Playlists</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @forelse ($playlists as $playlist)
                                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                    <h3 class="font-semibold">{{ $playlist->title }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $playlist->tracks_count ?? 0 }} tracks</p>
                                    <a href="{{ route('playlists.show', $playlist) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">View</a>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-4">No playlists found</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 