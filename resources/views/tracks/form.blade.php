@props(['track' => null, 'submitRoute', 'submitMethod' => 'POST'])

<div class="bg-white rounded-lg shadow-md p-6">
    <x-heading :level="2" class="mb-4">
        {{ $track ? 'Edit Track' : 'Add New Track' }}
    </x-heading>

    <x-form-validation-summary :errors="$errors" />

    <form action="{{ $submitRoute }}" method="POST">
        @csrf
        @if($submitMethod !== 'POST')
            @method($submitMethod)
        @endif

        <div class="mb-4">
            <x-input 
                id="title" 
                name="title" 
                type="text" 
                label="Track Name"
                value="{{ old('title', $track ? $track->title : null) }}" 
                required 
                helpText="The name of the track"
                tooltip="Enter the title of the track as it should appear to users."
            />
        </div>

        <div class="mb-4">
            <x-input 
                id="audio_url" 
                name="audio_url" 
                type="url" 
                label="Audio URL"
                value="{{ old('audio_url', $track ? $track->audio_url : null) }}" 
                required 
                helpText="Direct URL to an audio file (MP3, WAV, etc.)"
                tooltip="Provide a direct link to the audio file. The URL should end with a file extension like .mp3, .wav, etc."
                tooltipPosition="right"
            />
        </div>

        <div class="mb-4">
            <x-input 
                id="image_url" 
                name="image_url" 
                type="url" 
                label="Cover Image URL"
                value="{{ old('image_url', $track ? $track->image_url : null) }}" 
                helpText="Direct URL to an image file (JPG, PNG, etc.)"
                tooltip="Add a URL to the album art or cover image. Square images work best. If left empty, a default image will be shown."
            />
        </div>

        <div class="mb-4">
            <x-input 
                id="duration" 
                name="duration" 
                type="text" 
                label="Duration"
                value="{{ old('duration', $track ? $track->duration : null) }}" 
                placeholder="3:30" 
                helpText="Track duration in minutes:seconds format (e.g., 3:45)"
                tooltip="Enter the duration of the track in minutes:seconds format (e.g., 3:45). This is displayed to users but not used for playback."
            />
        </div>

        <div class="mb-4">
            <x-label-with-tooltip 
                for="genre_ids" 
                value="Genres" 
                required 
                tooltip="Select at least one genre for the track. Hold Ctrl/Cmd key to select multiple genres."
                tooltipPosition="top"
                class="mb-1"
            />
            
            <select 
                id="genre_ids" 
                name="genre_ids[]" 
                multiple 
                class="select select-bordered w-full {{ $errors->has('genre_ids') || $errors->has('genres') ? 'select-error' : '' }}"
                required
                onchange="updateGenresString()"
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
            
            <!-- Hidden field for genres string (for API compatibility) -->
            <input type="hidden" name="genres" id="genres_string" value="{{ old('genres', $track ? $track->genres->pluck('name')->implode(', ') : '') }}">
            
            @if($errors->has('genre_ids') || $errors->has('genres'))
                <div class="mt-1 text-sm text-error">
                    {{ $errors->first('genre_ids') ?: $errors->first('genres') }}
                </div>
            @endif
            
            <p class="mt-1 text-sm text-gray-500">Hold Ctrl/Cmd to select multiple genres</p>
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
            <x-tooltip text="Save the current track information" position="top">
                <x-button 
                    type="submit" 
                    variant="primary"
                >
                    <x-icon name="plus" class="mr-1" />
                    {{ $track ? 'Update Track' : 'Save Track' }}
                </x-button>
            </x-tooltip>
        </div>
    </form>
</div>

<script>
function updateGenresString() {
    const selectElement = document.getElementById('genre_ids');
    const selectedGenres = Array.from(selectElement.selectedOptions).map(option => option.text);
    document.getElementById('genres_string').value = selectedGenres.join(', ');
}

// Run this on page load to initialize the hidden field
document.addEventListener('DOMContentLoaded', updateGenresString);
</script> 