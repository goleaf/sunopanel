<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Edit Playlist') }}: {{ $playlist->title }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <x-button href="{{ route('playlists.show', $playlist) }}" color="ghost">
                <x-icon name="arrow-left" size="5" class="mr-2" />
                Back to Playlist
            </x-button>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-6">
                    {{ __('Edit Playlist') }}
                </h2>

                @if ($errors->any())
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('playlists.update', $playlist) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-control mb-4">
                        <label for="title" class="label">
                            <span class="label-text">Playlist Title <span class="text-error">*</span></span>
                        </label>
                        <input 
                            id="title" 
                            name="title" 
                            type="text" 
                            value="{{ old('title', $playlist->title) }}" 
                            class="input input-bordered w-full @error('title') input-error @enderror" 
                            required 
                        />
                        @error('title')
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label for="description" class="label">
                            <span class="label-text">Description</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="3" 
                            class="textarea textarea-bordered w-full @error('description') textarea-error @enderror"
                        >{{ old('description', $playlist->description) }}</textarea>
                        @error('description')
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label for="cover_image" class="label">
                            <span class="label-text">Cover Image URL</span>
                        </label>
                        <input 
                            id="cover_image" 
                            name="cover_image" 
                            type="url" 
                            value="{{ old('cover_image', $playlist->cover_image) }}" 
                            class="input input-bordered w-full @error('cover_image') input-error @enderror" 
                            placeholder="https://example.com/image.jpg"
                        />
                        @error('cover_image')
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror

                        @if($playlist->cover_image)
                            <div class="mt-2">
                                <div class="avatar">
                                    <div class="w-32 rounded">
                                        <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->title }}">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="form-control mb-6">
                        <label for="genre_id" class="label">
                            <span class="label-text">Genre</span>
                        </label>
                        <x-genres-dropdown :selectedGenre="$playlist->genre_id" />
                        @error('genre_id')
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <x-button href="{{ route('playlists.show', $playlist) }}" color="ghost" class="mr-2">
                            Cancel
                        </x-button>
                        <x-button type="submit" color="primary">
                            Update Playlist
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
