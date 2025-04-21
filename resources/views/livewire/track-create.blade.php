<x-app-layout>
    <x-slot name="header">
        Add New Track
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div class="breadcrumbs text-sm">
                    <ul>
                        <li><a href="{{ route('tracks.index') }}">Tracks</a></li> 
                        <li>Add New Track</li>
                    </ul>
                </div>
                <x-button href="{{ route('tracks.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-sm-left" size="4" class="mr-1" />
                    Back
                </x-button>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    @if (session()->has('error'))
                        <div class="alert alert-error mb-4">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <form wire:submit.prevent="saveTrack" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Track Title</span>
                                </label>
                                <input type="text" wire:model="title" class="input input-bordered" placeholder="Enter track title">
                                @error('title') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Artist</span>
                                </label>
                                <input type="text" wire:model="artist" class="input input-bordered" placeholder="Enter artist name">
                                @error('artist') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Album</span>
                                </label>
                                <input type="text" wire:model="album" class="input input-bordered" placeholder="Enter album name">
                                @error('album') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Duration (MM:SS)</span>
                                </label>
                                <input type="text" wire:model="duration" class="input input-bordered" placeholder="E.g., 3:45">
                                @error('duration') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Genres</span>
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach($genres as $genre)
                                    <label class="label cursor-pointer justify-start">
                                        <input type="checkbox" wire:model="selectedGenres" value="{{ $genre->id }}" class="checkbox checkbox-primary mr-2">
                                        <span class="label-text">{{ $genre->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedGenres') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Audio File</span>
                                </label>
                                <input type="file" wire:model="audioFile" class="file-input file-input-bordered w-full" accept=".mp3,.wav,.ogg">
                                <div class="text-sm text-gray-500 mt-1">Supported formats: MP3, WAV, OGG (max 20MB)</div>
                                @error('audioFile') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Cover Image (Optional)</span>
                                </label>
                                <input type="file" wire:model="imageFile" class="file-input file-input-bordered w-full" accept="image/*">
                                <div class="text-sm text-gray-500 mt-1">Recommended size: 300x300px (max 5MB)</div>
                                @error('imageFile') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        @if($imageFile)
                            <div class="mt-2">
                                <label class="label">
                                    <span class="label-text">Image Preview</span>
                                </label>
                                <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview" class="max-w-xs rounded-lg shadow-md">
                            </div>
                        @endif
                        
                        <div class="card-actions justify-end mt-6">
                            <a href="{{ route('tracks.index') }}" class="btn">Cancel</a>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveTrack,audioFile,imageFile">
                                <span wire:loading.remove wire:target="saveTrack">Save Track</span>
                                <span wire:loading wire:target="saveTrack">
                                    <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 