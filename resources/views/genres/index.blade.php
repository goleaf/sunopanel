<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Genres') }}
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
                    <div class="w-full md:w-1/2">
                        <form method="GET" action="{{ route('genres.index') }}" class="flex">
                            <div class="relative flex-grow">
                                <x-search-input placeholder="Search genres..." />
                            </div>
                            <div class="ml-2">
                                <x-button type="submit" color="indigo">
                                    <x-icon name="search" class="h-5 w-5 mr-1" />
                                    Search
                                </x-button>
                            </div>
                        </form>
                    </div>
                    <div>
                        <x-button href="{{ route('genres.create') }}" color="indigo">
                            <x-icon name="plus" class="h-5 w-5 mr-1" />
                            {{ __('Create Genre') }}
                        </x-button>
                    </div>
                </div>

                <x-data-table>
                    <x-slot name="header">
                        <x-th sortable field="name" :sort="request('sort')" :direction="request('direction', 'asc')">
                            {{ __('Name') }}
                        </x-th>
                        <x-th sortable field="tracks_count" :sort="request('sort')" :direction="request('direction', 'asc')">
                            {{ __('Tracks Count') }}
                        </x-th>
                        <x-th sortable field="created_at" :sort="request('sort')" :direction="request('direction', 'asc')">
                            {{ __('Created At') }}
                        </x-th>
                        <x-th>
                            {{ __('Actions') }}
                        </x-th>
                    </x-slot>

                    <x-slot name="body">
                        @forelse($genres as $genre)
                            <tr class="hover:bg-gray-50">
                                <x-td>
                                    <a href="{{ route('genres.show', $genre) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        {{ $genre->name }}
                                    </a>
                                </x-td>
                                <x-td>
                                    {{ $genre->tracks_count }}
                                </x-td>
                                <x-td>
                                    {{ $genre->created_at->format('Y-m-d') }}
                                </x-td>
                                <x-td>
                                    <x-action-buttons>
                                        <x-row-action href="{{ route('genres.show', $genre) }}" icon="eye" label="View" />
                                        <x-row-action href="{{ route('genres.edit', $genre) }}" icon="pencil" label="Edit" />
                                        
                                        <form method="POST" action="{{ route('genres.destroy', $genre) }}" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                class="text-red-600 hover:text-red-900 inline-flex items-center" 
                                                onclick="return confirm('{{ __('Are you sure you want to delete this genre?') }}')">
                                                <x-icon name="trash" class="h-5 w-5 mr-1" />
                                                <span>{{ __('Delete') }}</span>
                                            </button>
                                        </form>
                                        
                                        <x-row-action href="{{ route('playlists.create-from-genre', $genre) }}" icon="collection" label="{{ __('Create Playlist') }}" color="green" />
                                    </x-action-buttons>
                                </x-td>
                            </tr>
                        @empty
                            <tr>
                                <x-td colspan="4" class="text-center">
                                    {{ __('No genres found') }}
                                </x-td>
                            </tr>
                        @endforelse
                    </x-slot>
                </x-data-table>

                <div class="mt-4">
                    {{ $genres->links('components.pagination-links') }}
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
