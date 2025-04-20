<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Genres') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-page-header title="Genres" />
            
            <!-- Add Genre Form Card -->
            <x-card class="mb-8" id="genre-form">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">
                        {{ $editingGenreId ? __('Edit Genre') : __('Add New Genre') }}
                    </h2>
                    
                    <form wire:submit.prevent="{{ $editingGenreId ? 'update' : 'create' }}">
                        <div class="space-y-4">
                            <x-form.group label="Name" for="name" required :error="$errors->first('name')">
                                <x-input id="name" wire:model="name" placeholder="Enter genre name" required />
                            </x-form.group>
                            
                            <x-form.group label="Description" for="description" :error="$errors->first('description')">
                                <x-input id="description" wire:model="description" placeholder="Enter genre description" />
                            </x-form.group>
                        </div>
                        
                        <div class="mt-6">
                            <x-button type="submit" color="primary">
                                {{ $editingGenreId ? __('Update') : __('Create') }}
                            </x-button>
                            @if($editingGenreId)
                                <x-button type="button" color="outline" wire:click="resetInput" class="ml-3">
                                    {{ __('Cancel') }}
                                </x-button>
                            @endif
                        </div>
                    </form>
                </div>
            </x-card>

            <!-- Genres List Card -->
            <x-card>
                <x-slot name="title">Genres</x-slot>
                <x-slot name="actions">
                    <div class="flex">
                        <x-search-input wire="search" placeholder="Search genres..." class="w-64 mr-3" />
                        <x-create-button wire="createGenre" text="Create Genre" />
                    </div>
                </x-slot>
                
                <div class="overflow-x-auto -mx-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th wire:click="sortBy('name')" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        Name
                                        @if($sortField === 'name')
                                            <span class="ml-1">
                                                @if($direction === 'asc')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description
                                </th>
                                <th wire:click="sortBy('tracks_count')" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        Tracks
                                        @if($sortField === 'tracks_count')
                                            <span class="ml-1">
                                                @if($direction === 'asc')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th wire:click="sortBy('created_at')" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        Created
                                        @if($sortField === 'created_at')
                                            <span class="ml-1">
                                                @if($direction === 'asc')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($genres as $genre)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('genres.show', $genre) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ Str::limit($genre->name, 40) }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ Str::limit($genre->description, 50) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $genre->tracks_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span title="{{ $genre->created_at->format('Y-m-d H:i:s') }}">
                                            {{ $genre->created_at->diffForHumans(null, true) }} ago
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('genres.show', $genre) }}" class="text-indigo-600 hover:text-indigo-900" title="View Genre">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <button wire:click="edit({{ $genre->id }})" class="text-indigo-600 hover:text-indigo-900" title="Edit Genre">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="confirmDelete({{ $genre->id }})" class="text-red-600 hover:text-red-900" title="Delete Genre">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center">
                                        <div class="text-gray-500">
                                            <p class="text-lg mb-4">No genres found.</p>
                                            <x-create-button wire="createGenre" text="Create Your First Genre" />
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
            </x-card>
        </div>
        
        <script>
            document.addEventListener('livewire:load', function () {
                // Handle scroll-to-form event
                window.addEventListener('scroll-to-form', event => {
                    const formElement = document.getElementById('genre-form');
                    if (formElement) {
                        formElement.scrollIntoView({ behavior: 'smooth' });
                        
                        // Focus on the input field after scrolling
                        setTimeout(() => {
                            const inputElement = document.getElementById(event.detail.id);
                            if (inputElement) {
                                inputElement.focus();
                            }
                        }, 500);
                    }
                });
            });
        </script>
    </div>
</x-app-layout> 