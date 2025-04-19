@extends('layouts.app')

@section('content')
{{-- Determine if we are editing or creating --}}
@php
    $isEditing = isset($playlist) && $playlist->exists;
    $formAction = $isEditing ? route('playlists.update', $playlist) : route('playlists.store');
    $pageTitle = $isEditing ? 'Edit Playlist: ' . Str::limit($playlist->title, 50) : 'Create New Playlist';
    $buttonText = $isEditing ? 'Update Playlist' : 'Save Playlist';
    $buttonIcon = $isEditing ? 'save' : 'plus';
@endphp

{{-- Page Header --}}
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold text-base-content truncate" @if($isEditing) title="{{ $playlist->title }}" @endif>{{ $pageTitle }}</h1>
    <x-button 
        href="{{ $isEditing ? route('playlists.show', $playlist) : route('playlists.index') }}" 
        variant="outline"
        size="sm"
    >
        <x-icon name="arrow-sm-left" size="4" class="mr-1" />
        Back
    </x-button>
</div>

{{-- Form Card --}}
<div class="card bg-base-100 shadow-xl max-w-3xl mx-auto">
    <form action="{{ $formAction }}" method="POST" class="card-body space-y-6">
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <h2 class="card-title text-lg">{{ $isEditing ? 'Playlist Details' : 'Enter Playlist Details' }}</h2>

        <x-form-validation-summary :errors="$errors" />

        {{-- Playlist Title --}}
        <div class="form-control">
            <x-input
                id="title"
                name="title"
                type="text"
                label="Playlist Title"
                value="{{ old('title', $playlist?->title) }}"
                required
                autofocus
                helpText="The main name for this playlist."
                :error="$errors->first('title')"
            />
        </div>

        {{-- Playlist Description --}}
        <div class="form-control">
            <x-textarea
                id="description"
                name="description"
                label="Description"
                rows="4"
                helpText="A short description of the playlist (optional)."
                :error="$errors->first('description')"
            >{{ old('description', $playlist?->description) }}</x-textarea>
        </div>

         {{-- Cover Image --}}
        <div class="form-control">
             <x-input
                 id="cover_image"
                 name="cover_image"
                 type="url" {{-- Assuming URL for now, adjust if using file upload --}}
                 label="Cover Image URL"
                 value="{{ old('cover_image', $playlist?->cover_image) }}"
                 placeholder="https://example.com/image.jpg"
                 helpText="URL for the playlist cover art (optional)."
                 :error="$errors->first('cover_image')"
             />
             {{-- Display current image if editing --}}
             @if ($isEditing && $playlist?->cover_image)
                 <div class="mt-2">
                     <img src="{{ $playlist->cover_image }}" alt="Current cover" class="h-20 w-20 object-cover rounded">
                 </div>
             @endif
        </div>

        {{-- Genre --}}
        <div class="form-control">
            <x-label for="genre_id" value="Genre (Optional)" />
            <x-select 
                id="genre_id" 
                name="genre_id" 
                class="select-bordered w-full"
                :error="$errors->first('genre_id')"
            >
                <option value="">-- Select Genre --</option>
                @php
                    // Ensure $genres is available (passed from controller)
                    $availableGenres = $genres ?? \App\Models\Genre::orderBy('name')->get();
                @endphp
                @foreach($availableGenres as $genre)
                    <option 
                        value="{{ $genre->id }}" 
                        {{ old('genre_id', isset($playlist) ? $playlist?->genre_id : null) == $genre->id ? 'selected' : '' }}
                    >
                        {{ $genre->name }}
                    </option>
                @endforeach
            </x-select>
             <x-input-error :messages="$errors->get('genre_id')" for="genre_id" class="mt-2" />
             <label class="label">
                <span class="label-text-alt text-base-content/70">Associate this playlist with a primary genre.</span>
             </label>
        </div>

        {{-- Action Buttons --}}
        <div class="card-actions justify-end pt-4">
             <x-button
                href="{{ $isEditing ? route('playlists.show', $playlist) : route('playlists.index') }}"
                variant="ghost"
                size="sm"
            >
                 <x-icon name="x-mark" class="mr-1 h-4 w-4" />
                Cancel
            </x-button>
            <x-tooltip text="{{ $isEditing ? 'Update' : 'Save' }} this playlist" position="top">
                <x-button
                    type="submit"
                    variant="primary"
                    size="sm"
                >
                     <x-icon name="{{ $buttonIcon }}" class="mr-1 h-4 w-4" />
                    {{ $buttonText }}
                </x-button>
            </x-tooltip>
        </div>
    </form>
</div>
@endsection 