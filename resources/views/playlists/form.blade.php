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

        <div class="space-y-6">
            <div>
                <x-label for="title" :value="__('Title')" />
                <x-input 
                    id="title" 
                    class="block mt-1 w-full" 
                    type="text" 
                    name="title" 
                    :value="old('title', $playlist->title ?? '')" 
                    required 
                    autofocus 
                />
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-label for="description" :value="__('Description')" />
                <x-textarea 
                    id="description" 
                    class="block mt-1 w-full" 
                    name="description" 
                    :value="old('description', $playlist->description ?? '')" 
                />
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-label for="genre_id" :value="__('Genre (Optional)')" />
                <x-select 
                    id="genre_id" 
                    class="block mt-1 w-full" 
                    name="genre_id"
                >
                    <option value="">-- Select Genre --</option>
                    @foreach($genres as $genre)
                        <option 
                            value="{{ $genre->id }}" 
                            {{ (old('genre_id', $playlist->genre_id ?? '') == $genre->id) ? 'selected' : '' }}
                        >
                            {{ $genre->name }}
                        </option>
                    @endforeach
                </x-select>
                @error('genre_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-label for="cover_image" :value="__('Cover Image (Optional)')" />
                <div class="mt-1 flex items-center">
                    @if(isset($playlist) && $playlist->cover_image)
                    <div class="mr-4">
                        <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->title }}" class="h-32 w-32 object-cover rounded">
                    </div>
                    @endif
                    <x-input 
                        id="cover_image" 
                        class="flex-1" 
                        type="text" 
                        name="cover_image" 
                        placeholder="https://example.com/image.jpg"
                        :value="old('cover_image', $playlist->cover_image ?? '')" 
                    />
                </div>
                @error('cover_image')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-gray-500 text-xs mt-1">Enter a URL to an image</p>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button href="{{ $playlist ? route('playlists.show', $playlist) : route('playlists.index') }}" color="ghost" class="mr-2">
                Cancel
            </x-button>
            <x-button type="submit" color="primary">
                {{ $playlist ? 'Update Playlist' : 'Create Playlist' }}
            </x-button>
        </div>
    </form>
</div> 