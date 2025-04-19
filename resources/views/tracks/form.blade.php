@props(['track' => null, 'submitRoute', 'submitMethod' => 'POST'])

<div class="bg-white rounded-lg shadow-md p-6">
    <x-heading :level="2" class="mb-4">
        {{ $track ? 'Edit Track' : 'Add New Track' }}
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
            <x-label for="title" value="Track Name" required />
            <x-input 
                id="title" 
                name="title" 
                type="text" 
                value="{{ old('title', $track ? $track->title : null) }}" 
                class="w-full mt-1 @error('title') border-red-500 @enderror" 
                required 
            />
            @error('title')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <x-label for="audio_url" value="Audio URL" required />
            <x-input 
                id="audio_url" 
                name="audio_url" 
                type="url" 
                value="{{ old('audio_url', $track ? $track->audio_url : null) }}" 
                class="w-full mt-1 @error('audio_url') border-red-500 @enderror" 
                required 
            />
            @error('audio_url')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-gray-600 text-sm mt-1">Direct URL to an audio file (MP3, WAV, etc.)</p>
        </div>

        <div class="mb-4">
            <x-label for="image_url" value="Cover Image URL" />
            <x-input 
                id="image_url" 
                name="image_url" 
                type="url" 
                value="{{ old('image_url', $track ? $track->image_url : null) }}" 
                class="w-full mt-1 @error('image_url') border-red-500 @enderror" 
            />
            @error('image_url')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-gray-600 text-sm mt-1">Direct URL to an image file (JPG, PNG, etc.)</p>
        </div>

        <div class="mb-4">
            <x-label for="duration" value="Duration" />
            <x-input 
                id="duration" 
                name="duration" 
                type="text" 
                value="{{ old('duration', $track ? $track->duration : null) }}" 
                placeholder="3:30" 
                class="w-full mt-1 @error('duration') border-red-500 @enderror" 
            />
            @error('duration')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-gray-600 text-sm mt-1">Track duration in minutes:seconds format (e.g., 3:45)</p>
        </div>

        <div class="mb-4">
            <x-label for="genres" value="Genres" required />
            <select 
                id="genres" 
                name="genre_ids[]" 
                multiple 
                class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                required
            >
                @foreach(\App\Models\Genre::orderBy('name')->get() as $genre)
                    <option 
                        value="{{ $genre->id }}" 
                        {{ $track && $track->genres->contains($genre) ? 'selected' : '' }}
                        {{ in_array($genre->id, old('genre_ids', [])) ? 'selected' : '' }}
                    >
                        {{ $genre->name }}
                    </option>
                @endforeach
            </select>
            @error('genre_ids')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-gray-600 text-sm mt-1">Hold Ctrl/Cmd to select multiple genres</p>
        </div>

        <div class="flex justify-end">
            <x-button 
                href="{{ route('tracks.index') }}" 
                variant="light" 
                class="mr-2"
            >
                <x-icon name="arrow-left" class="mr-1" />
                Cancel
            </x-button>
            <x-button 
                type="submit" 
                variant="primary"
            >
                <x-icon name="plus" class="mr-1" />
                {{ $track ? 'Update Track' : 'Save Track' }}
            </x-button>
        </div>
    </form>
</div> 