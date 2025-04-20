<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Genres') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ $editingGenreId ? __('Edit Genre') : __('Add New Genre') }}
                </h3>
                <form wire:submit.prevent="{{ $editingGenreId ? 'update' : 'create' }}">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <x-form.label for="name" required>{{ __('Name') }}</x-form.label>
                            <x-form.input id="name" name="name" type="text" wire:model="name" placeholder="{{ __('Enter genre name') }}" required />
                            @error('name') <x-form.error name="name">{{ $message }}</x-form.error> @enderror
                        </div>
                        <div>
                            <x-form.label for="description">{{ __('Description') }}</x-form.label>
                            <x-form.input id="description" name="description" type="text" wire:model="description" placeholder="{{ __('Enter genre description') }}" />
                            @error('description') <x-form.error name="description">{{ $message }}</x-form.error> @enderror
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <button type="submit" class="btn btn-primary">
                            {{ $editingGenreId ? __('Update') : __('Create') }}
                        </button>
                        @if($editingGenreId)
                            <button type="button" class="btn btn-secondary" wire:click="resetInput">
                                {{ __('Cancel') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h1 class="text-2xl font-semibold text-base-content">Genres</h1>
                        <div class="flex space-x-2 w-full sm:w-auto">
                            <form wire:submit.prevent="updatingSearch" class="join flex-grow">
                                <input type="text" wire:model.debounce.500ms="search" placeholder="Search genres..." class="input input-bordered join-item input-sm w-full" />
                                <button type="submit" class="btn btn-primary join-item btn-sm">
                                    <x-icon name="search" size="4" />
                                </button>
                            </form>
                            <x-button wire:click="createGenre" variant="primary" size="sm" class="flex-shrink-0">
                                <x-icon name="plus" size="4" class="mr-1" />
                                Create Genre
                            </x-button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra table-sm w-full">
                            <thead>
                                <tr>
                                    <th wire:click="sortBy('name')" class="cursor-pointer">
                                        Name
                                        @if($sortField === 'name')
                                            <span class="ml-1">
                                                @if($direction === 'asc')
                                                    <x-icon name="arrow-up" size="4" />
                                                @else
                                                    <x-icon name="arrow-down" size="4" />
                                                @endif
                                            </span>
                                        @endif
                                    </th>
                                    <th>Description</th>
                                    <th wire:click="sortBy('tracks_count')" class="cursor-pointer">
                                        Tracks
                                        @if($sortField === 'tracks_count')
                                            <span class="ml-1">
                                                @if($direction === 'asc')
                                                    <x-icon name="arrow-up" size="4" />
                                                @else
                                                    <x-icon name="arrow-down" size="4" />
                                                @endif
                                            </span>
                                        @endif
                                    </th>
                                    <th wire:click="sortBy('created_at')" class="cursor-pointer">
                                        Created
                                        @if($sortField === 'created_at')
                                            <span class="ml-1">
                                                @if($direction === 'asc')
                                                    <x-icon name="arrow-up" size="4" />
                                                @else
                                                    <x-icon name="arrow-down" size="4" />
                                                @endif
                                            </span>
                                        @endif
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($genres as $genre)
                                    <tr>
                                        <td>
                                            <a href="{{ route('genres.show', $genre) }}" class="font-bold hover:text-primary transition duration-150 ease-in-out">
                                                {{ Str::limit($genre->name, 40) }}
                                            </a>
                                        </td>
                                        <td class="text-sm text-base-content/70">
                                            {{ Str::limit($genre->description, 50) }}
                                        </td>
                                        <td>
                                            <span class="badge badge-ghost badge-sm">{{ $genre->tracks_count }}</span>
                                        </td>
                                        <td>
                                            <span title="{{ $genre->created_at->format('Y-m-d H:i:s') }}">
                                                {{ $genre->created_at->diffForHumans(null, true) }} ago
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center space-x-1">
                                                <x-tooltip text="View Genre" position="top">
                                                    <x-button href="{{ route('genres.show', $genre) }}" variant="ghost" size="xs" icon>
                                                        <x-icon name="eye" />
                                                    </x-button>
                                                </x-tooltip>
                                                <x-tooltip text="Edit Genre" position="top">
                                                    <x-button href="{{ route('genres.edit', $genre) }}" variant="ghost" size="xs" icon>
                                                        <x-icon name="pencil" />
                                                    </x-button>
                                                </x-tooltip>
                                                <x-tooltip text="Delete Genre" position="top">
                                                    <form action="{{ route('genres.destroy', $genre) }}" method="POST" onsubmit="return confirm('Delete genre: {{ addslashes($genre->name) }}?')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-button type="submit" variant="ghost" color="error" size="xs" icon>
                                                            <x-icon name="trash" />
                                                        </x-button>
                                                    </form>
                                                </x-tooltip>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-10">
                                            <div class="text-base-content/70">
                                                <p class="text-lg mb-2">No genres found.</p>
                                                <x-button wire:click="createGenre" variant="primary" size="sm">
                                                    <x-icon name="plus" class="mr-1" /> Create Your First Genre
                                                </x-button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $genres->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 