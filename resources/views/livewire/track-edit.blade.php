<x-app-layout>
    <x-slot name="header">
        Edit Track: {{ $originalTitle }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div class="breadcrumbs text-sm">
                    <ul>
                        <li><a href="{{ route('tracks.index') }}">Tracks</a></li> 
                        <li>Edit Track</li>
                    </ul>
                </div>
                <div class="flex space-x-2">
                    <x-button href="{{ route('tracks.index') }}" variant="ghost" size="sm">
                        <x-icon name="arrow-sm-left" size="4" class="mr-1" />
                        Back to List
                    </x-button>
                    <x-button href="{{ route('tracks.show', $track) }}" variant="ghost" size="sm">
                        <x-icon name="eye" size="4" class="mr-1" />
                        View Details
                    </x-button>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-xl">Edit Track</h2>

                    <div>
                        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
                            <h2 class="text-xl font-semibold mb-4">Edit Track</h2>
                            
                            <form wire:submit.prevent="updateTrack" class="space-y-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Title <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="title" wire:model="title" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="artist" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Artist
                                    </label>
                                    <input type="text" id="artist" wire:model="artist" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('artist') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="album" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Album
                                    </label>
                                    <input type="text" id="album" wire:model="album" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('album') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Duration (MM:SS)
                                    </label>
                                    <input type="text" id="duration" wire:model="duration" placeholder="03:45" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('duration') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="genres" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Genres
                                    </label>
                                    <div class="mt-2 space-y-2">
                                        @foreach($genres as $genre)
                                            <label class="inline-flex items-center mr-3">
                                                <input type="checkbox" wire:model="selectedGenres" value="{{ $genre->id }}" 
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $genre->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('selectedGenres') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="audioFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Audio File
                                    </label>
                                    <div class="mt-1 flex items-center">
                                        <input type="file" id="audioFile" wire:model="audioFile" accept=".mp3,.wav,.ogg" 
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-indigo-400">
                                    </div>
                                    <div class="mt-2">
                                        @if($currentAudioUrl)
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Current file: </span>
                                                <audio controls class="ml-2 h-8 w-64">
                                                    <source src="{{ $currentAudioUrl }}" type="audio/mpeg">
                                                    Your browser does not support the audio element.
                                                </audio>
                                            </div>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="audioFile">
                                        <span class="text-sm text-indigo-600 dark:text-indigo-400">Uploading...</span>
                                    </div>
                                    @error('audioFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="imageFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Cover Image
                                    </label>
                                    <div class="mt-1 flex items-center">
                                        <input type="file" id="imageFile" wire:model="imageFile" accept="image/*" 
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-indigo-400">
                                    </div>
                                    <div class="mt-2">
                                        @if($currentImageUrl)
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Current image: </span>
                                                <img src="{{ $currentImageUrl }}" alt="Track cover" class="ml-2 h-16 w-16 object-cover rounded">
                                            </div>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="imageFile">
                                        <span class="text-sm text-indigo-600 dark:text-indigo-400">Uploading...</span>
                                    </div>
                                    @error('imageFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="flex justify-end space-x-3 pt-4">
                                    <a href="{{ route('tracks.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">
                                        Cancel
                                    </a>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Update Track
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 