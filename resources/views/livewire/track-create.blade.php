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
                    <h2 class="card-title text-xl">Create New Track</h2>

                    <form wire:submit.prevent="save" class="space-y-6 mt-4">
                        <div class="form-control w-full">
                            <label for="title" class="label">
                                <span class="label-text font-medium">Title <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="title" wire:model="title" 
                                class="input input-bordered w-full @error('title') input-error @enderror" />
                            @error('title') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-control w-full">
                            <label for="audio_url" class="label">
                                <span class="label-text font-medium">Audio URL <span class="text-error">*</span></span>
                            </label>
                            <input type="url" id="audio_url" wire:model="audio_url" 
                                class="input input-bordered w-full @error('audio_url') input-error @enderror" />
                            @error('audio_url') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-control w-full">
                            <label for="image_url" class="label">
                                <span class="label-text font-medium">Image URL <span class="text-error">*</span></span>
                            </label>
                            <input type="url" id="image_url" wire:model="image_url" 
                                class="input input-bordered w-full @error('image_url') input-error @enderror" />
                            @error('image_url') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-control w-full">
                            <label for="duration" class="label">
                                <span class="label-text font-medium">Duration (mm:ss)</span>
                            </label>
                            <input type="text" id="duration" wire:model="duration" placeholder="03:45" 
                                class="input input-bordered w-full @error('duration') input-error @enderror" />
                            @error('duration') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-medium">Genres</span>
                            </label>
                            
                            <div class="flex flex-col space-y-2">
                                <!-- Select from existing genres -->
                                <div class="form-control">
                                    <label class="label cursor-pointer justify-start gap-2">
                                        <span class="label-text">Select from existing genres:</span>
                                    </label>
                                    <select multiple class="select select-bordered w-full" wire:model="selectedGenres">
                                        @foreach($allGenres as $genre)
                                            <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple genres</p>
                                </div>
                                
                                <!-- OR enter new genres -->
                                <div class="divider text-xs">OR</div>
                                
                                <div class="form-control">
                                    <label class="label cursor-pointer justify-start gap-2">
                                        <span class="label-text">Enter new genres (comma-separated):</span>
                                    </label>
                                    <input type="text" wire:model="genres" 
                                        placeholder="Pop, Rock, Jazz" 
                                        class="input input-bordered w-full @error('genres') input-error @enderror" />
                                    <p class="text-xs text-gray-500 mt-1">New genres will be created automatically</p>
                                </div>
                            </div>
                            
                            @error('selectedGenres') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                            @error('genres') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-2 mt-6">
                            <x-button href="{{ route('tracks.index') }}" variant="outline" type="button">
                                Cancel
                            </x-button>
                            <x-button type="submit" variant="primary">
                                Create Track
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 