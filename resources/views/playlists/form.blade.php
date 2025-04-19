@props(['playlist' => null, 'submitRoute', 'submitMethod' => 'POST'])

<div class="bg-white rounded-lg shadow-md p-6">
    <x-heading :level="2" class="mb-4">
        {{ $playlist ? 'Edit Playlist' : 'Create New Playlist' }}
    </x-heading>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $submitRoute }}" method="POST">
        @csrf
        @if($submitMethod !== 'POST')
            @method($submitMethod)
        @endif

        <div class="mb-4">
            <x-label for="name" value="Playlist Name" required />
            <x-input 
                id="name" 
                name="name" 
                type="text" 
                value="{{ old('name', $playlist?->name) }}" 
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
            >{{ old('description', $playlist?->description) }}</x-textarea>
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
                value="{{ old('cover_image', $playlist?->cover_image) }}" 
                class="w-full mt-1" 
                placeholder="https://example.com/image.jpg"
            />
            @error('cover_image')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror

            @if($playlist && $playlist->cover_image)
                <div class="mt-2">
                    <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->name }}" class="h-32 w-32 object-cover rounded">
                </div>
            @endif
        </div>

        <div class="mb-4">
            <x-label for="genre_id" value="Genre" />
            <x-genres-dropdown :selectedGenre="$playlist?->genre_id" />
            @error('genre_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <x-button href="{{ $playlist ? route('playlists.show', $playlist) : route('playlists.index') }}" color="gray" class="mr-2">
                Cancel
            </x-button>
            <x-button type="submit" color="indigo">
                {{ $playlist ? 'Update Playlist' : 'Create Playlist' }}
            </x-button>
        </div>
    </form>
</div> 