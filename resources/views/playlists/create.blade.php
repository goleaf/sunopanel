<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Playlist') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <x-button href="{{ route('playlists.index') }}" color="gray">
                    <x-icon name="arrow-left" class="h-5 w-5 mr-1" />
                    Back to Playlists
                </x-button>
            </div>

            <x-card rounded="lg">
                <div class="bg-white p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-6">
                        {{ __('Create New Playlist') }}
                    </h2>

                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('playlists.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <x-label for="name" value="Playlist Name" required />
                            <x-input 
                                id="name" 
                                name="name" 
                                type="text" 
                                value="{{ old('name') }}" 
                                class="w-full mt-1" 
                                required 
                            />
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <x-label for="description" value="Description" />
                            <x-textarea 
                                id="description" 
                                name="description" 
                                rows="3" 
                                class="w-full mt-1"
                            >{{ old('description') }}</x-textarea>
                            @error('description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <x-label for="cover_image" value="Cover Image URL" />
                            <x-input 
                                id="cover_image" 
                                name="cover_image" 
                                type="url" 
                                value="{{ old('cover_image') }}" 
                                class="w-full mt-1" 
                                placeholder="https://example.com/image.jpg"
                            />
                            @error('cover_image')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <x-label for="genre_id" value="Genre" />
                            <x-genres-dropdown />
                            @error('genre_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <x-button href="{{ route('playlists.index') }}" color="gray" class="mr-2">
                                Cancel
                            </x-button>
                            <x-button type="submit" color="indigo">
                                Create Playlist
                            </x-button>
                        </div>
                    </form>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
