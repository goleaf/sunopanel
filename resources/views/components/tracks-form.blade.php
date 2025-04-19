@props([
    'track' => null,
    'submitRoute',
    'submitMethod' => 'POST'
])

<form action="{{ $submitRoute }}" method="POST" class="space-y-6">
    @csrf
    @if($submitMethod !== 'POST')
        @method($submitMethod)
    @endif

    <div>
        <x-label for="title" value="Track Name" required />
        <x-input id="title" name="title" type="text" class="w-full mt-1" 
            value="{{ old('title', $track ? $track->title : null) }}" required autofocus />
        @error('title')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="audio_url" value="Audio URL" required />
        <x-input id="audio_url" name="audio_url" type="url" class="w-full mt-1" 
            value="{{ old('audio_url', $track ? $track->audio_url : null) }}" required
            placeholder="https://example.com/audio.mp3" />
        @error('audio_url')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="image_url" value="Cover Image URL" required />
        <x-input id="image_url" name="image_url" type="url" class="w-full mt-1" 
            value="{{ old('image_url', $track ? $track->image_url : null) }}" required
            placeholder="https://example.com/image.jpg" />
        @error('image_url')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="genres" value="Genres (comma separated)" required />
        <x-input id="genres" name="genres" type="text" class="w-full mt-1" 
            value="{{ old('genres', $track ? $track->genres->pluck('name')->implode(', ') : null) }}" required
            placeholder="Rock, Pop, Jazz" />
        @error('genres')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-500 mt-1">Enter genres separated by commas (e.g., "Rock, Pop, Jazz")</p>
    </div>

    <div>
        <x-label for="duration" value="Duration (optional)" />
        <x-input id="duration" name="duration" type="text" class="w-full mt-1" 
            value="{{ old('duration', $track ? $track->duration : null) }}" 
            placeholder="3:45" />
        @error('duration')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-500 mt-1">Format: minutes:seconds (e.g., "3:45")</p>
    </div>

    <div class="flex justify-end">
        <x-button type="submit" color="indigo">
            {{ $track ? 'Update Track' : 'Create Track' }}
        </x-button>
    </div>
</form> 