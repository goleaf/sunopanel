<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $playlist->name }}
            @if($playlist->genre)
                <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                    {{ $playlist->genre->name }}
                </span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap gap-2">
                <x-button href="{{ route('playlists.index') }}" color="gray">
                    <x-icon name="arrow-left" class="h-5 w-5 mr-1" />
                    Back to Playlists
                </x-button>
                <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="green">
                    <x-icon name="plus" class="h-5 w-5 mr-1" />
                    Add Tracks
                </x-button>
                <x-button href="{{ route('playlists.edit', $playlist) }}" color="indigo">
                    <x-icon name="pencil" class="h-5 w-5 mr-1" />
                    Edit
                </x-button>
            </div>

            @if (session('success'))
                <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
            @endif

            @if (session('error'))
                <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                <!-- Playlist Details -->
                <div class="lg:col-span-1">
                    <x-card rounded="lg" class="mb-6">
                        <x-slot name="title">Playlist Details</x-slot>
                        
                        <div class="space-y-4">
                            @if($playlist->cover_image)
                                <div class="flex justify-center">
                                    <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->name }}" 
                                        class="w-full h-auto object-cover rounded-lg shadow">
                                </div>
                            @endif
                            
                            @if($playlist->description)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-1">Description</h4>
                                    <p class="text-gray-700">{{ $playlist->description }}</p>
                                </div>
                            @endif
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Created</h4>
                                <p class="text-gray-700">{{ $playlist->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Tracks</h4>
                                <p class="text-gray-700">{{ $playlist->tracks->count() }}</p>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-200">
                                <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" 
                                    onsubmit="return confirm('Are you sure you want to delete this playlist?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" color="red" class="w-full justify-center">
                                        <x-icon name="trash" class="h-5 w-5 mr-1" />
                                        Delete Playlist
                                    </x-button>
                                </form>
                            </div>
                        </div>
                    </x-card>
                </div>

                <!-- Tracks List -->
                <div class="lg:col-span-3">
                    <x-card rounded="lg">
                        <x-slot name="title">Tracks</x-slot>
                        <x-slot name="action">
                            <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="indigo" size="sm">
                                <x-icon name="plus" class="h-4 w-4 mr-1" />
                                Add Tracks
                            </x-button>
                        </x-slot>

                        @if($playlist->tracks->isEmpty())
                            <div class="text-gray-500 text-center py-8">
                                <p>No tracks in this playlist yet.</p>
                                <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="indigo" class="mt-4">
                                    Add Tracks to Playlist
                                </x-button>
                            </div>
                        @else
                            <x-data-table>
                                <x-slot name="header">
                                    <x-th>#</x-th>
                                    <x-th>Track</x-th>
                                    <x-th>Audio</x-th>
                                    <x-th>Genres</x-th>
                                    <x-th>Actions</x-th>
                                </x-slot>
                                <x-slot name="body">
                                    @foreach($playlist->tracks as $track)
                                        <tr class="hover:bg-gray-50">
                                            <x-td>{{ $track->pivot->position + 1 }}</x-td>
                                            <x-td>
                                                <div class="flex items-center">
                                                    <img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="h-10 w-10 object-cover rounded mr-3">
                                                    <div>
                                                        <div class="font-medium text-gray-900">{{ $track->title }}</div>
                                                    </div>
                                                </div>
                                            </x-td>
                                            <x-td>
                                                <x-audio-player :track="$track" />
                                            </x-td>
                                            <x-td>
                                                @if($track->genres->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($track->genres as $genre)
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                                {{ $genre->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-gray-500 text-xs">No genres</span>
                                                @endif
                                            </x-td>
                                            <x-td>
                                                <x-action-buttons>
                                                    <x-row-action href="{{ route('tracks.show', $track) }}" icon="eye" label="View" />
                                                    <form action="{{ route('playlists.remove-track', [$playlist->id, $track->id]) }}" method="POST" class="inline-flex">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                            class="text-red-600 hover:text-red-900 inline-flex items-center" 
                                                            onclick="return confirm('Are you sure you want to remove this track from the playlist?')">
                                                            <x-icon name="x-circle" class="h-5 w-5 mr-1" />
                                                            <span>Remove</span>
                                                        </button>
                                                    </form>
                                                </x-action-buttons>
                                            </x-td>
                                        </tr>
                                    @endforeach
                                </x-slot>
                            </x-data-table>
                        @endif
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
