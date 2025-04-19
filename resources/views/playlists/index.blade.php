<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Playlists') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
            @endif

            @if (session('error'))
                <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
            @endif

            <x-card rounded="lg" class="overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <div class="w-full md:w-2/3">
                        <x-search-form placeholder="Search playlists..." />
                    </div>
                    <div>
                        <x-button href="{{ route('playlists.create') }}" color="indigo">
                            <x-icon name="plus" class="h-5 w-5 mr-1" />
                            New Playlist
                        </x-button>
                    </div>
                </div>

                <x-data-table>
                    <x-slot name="header">
                        <x-th sortable field="name" :sort="request('sort')" :direction="request('direction', 'asc')">
                            Name
                        </x-th>
                        <x-th>
                            Description
                        </x-th>
                        <x-th sortable field="genre_id" :sort="request('sort')" :direction="request('direction', 'asc')">
                            Genre
                        </x-th>
                        <x-th sortable field="tracks_count" :sort="request('sort')" :direction="request('direction', 'asc')">
                            Tracks
                        </x-th>
                        <x-th sortable field="created_at" :sort="request('sort')" :direction="request('direction', 'asc')">
                            Created
                        </x-th>
                        <x-th>
                            Actions
                        </x-th>
                    </x-slot>

                    <x-slot name="body">
                        @forelse ($playlists as $playlist)
                            <tr class="hover:bg-gray-50">
                                <x-td>
                                    <div class="flex items-center">
                                        @if($playlist->cover_image)
                                            <div class="flex-shrink-0 mr-3">
                                                <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->name }}" class="h-10 w-10 object-cover rounded">
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('playlists.show', $playlist) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                {{ $playlist->name }}
                                            </a>
                                        </div>
                                    </div>
                                </x-td>
                                <x-td>
                                    <div class="text-sm text-gray-900 truncate max-w-xs">
                                        {{ Str::limit($playlist->description, 50) }}
                                    </div>
                                </x-td>
                                <x-td>
                                    @if($playlist->genre)
                                        <a href="{{ route('genres.show', $playlist->genre) }}" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 hover:bg-green-200">
                                            {{ $playlist->genre->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </x-td>
                                <x-td>
                                    <span class="text-sm">{{ $playlist->tracks_count }}</span>
                                </x-td>
                                <x-td>
                                    <span class="text-sm text-gray-500">{{ $playlist->created_at->format('Y-m-d') }}</span>
                                </x-td>
                                <x-td>
                                    <x-action-buttons>
                                        <x-row-action href="{{ route('playlists.show', $playlist) }}" icon="eye" label="View" />
                                        <x-row-action href="{{ route('playlists.edit', $playlist) }}" icon="pencil" label="Edit" />
                                        
                                        <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                class="text-red-600 hover:text-red-900 inline-flex items-center" 
                                                onclick="return confirm('Are you sure you want to delete this playlist?')">
                                                <x-icon name="trash" class="h-5 w-5 mr-1" />
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </x-action-buttons>
                                </x-td>
                            </tr>
                        @empty
                            <tr>
                                <x-td colspan="6" class="text-center">
                                    <div class="text-gray-500">
                                        No playlists found. 
                                        <a href="{{ route('playlists.create') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            Create your first playlist
                                        </a>
                                    </div>
                                </x-td>
                            </tr>
                        @endforelse
                    </x-slot>
                </x-data-table>

                <div class="mt-4">
                    {{ $playlists->links('components.pagination-links') }}
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
