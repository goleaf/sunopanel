@extends('layouts.app')

@section('content')
{{-- Determine if we are editing or creating --}}
@php
    $isEditing = isset($track) && $track->exists;
    $formAction = $isEditing ? route('tracks.update', $track) : route('tracks.store');
    $formMethod = $isEditing ? 'PUT' : 'POST';
    $pageTitle = $isEditing ? 'Edit Track: ' . Str::limit($track->title, 50) : 'Add New Track';
    $buttonText = $isEditing ? 'Update Track' : 'Save Track';
    $buttonIcon = $isEditing ? 'save' : 'plus';
@endphp

{{-- Page Header --}}
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold text-base-content truncate" @if($isEditing) title="{{ $track->title }}" @endif>{{ $pageTitle }}</h1>
    <x-button 
        href="{{ route('tracks.index') }}" 
        variant="outline"
        size="sm"
    >
        <x-icon name="arrow-sm-left" size="4" class="mr-1" />
        Back to Tracks
    </x-button>
</div>

{{-- Conditional Tabs for Create Mode --}}
<div x-data="{ activeTab: 'single' }" class="w-full">
    @if(!$isEditing)
        <div role="tablist" class="tabs tabs-lifted mb-6">
            <button 
                role="tab" 
                @click="activeTab = 'single'" 
                :class="{ 'tab-active': activeTab === 'single' }" 
                class="tab [--tab-bg:hsl(var(--b1))] [--tab-border-color:hsl(var(--b3))]">
                Single Track
            </button>
            <button 
                role="tab" 
                @click="activeTab = 'bulk'" 
                :class="{ 'tab-active': activeTab === 'bulk' }" 
                class="tab [--tab-bg:hsl(var(--b1))] [--tab-border-color:hsl(var(--b3))]">
                Bulk Import
            </button>
            <div role="tab" class="tab flex-grow cursor-default"></div> 
        </div>
    @endif

    {{-- Single Track Form Panel (or Edit Form) --}}
    <div x-show="activeTab === 'single' || @json($isEditing)" x-cloak>
        <div class="card bg-base-100 shadow-xl p-8">
            <h2 class="text-xl font-semibold mb-6">
                 {{ $isEditing ? 'Track Details' : 'Enter Track Details' }}
            </h2>

             <x-form-validation-summary :errors="$errors" />

             {{-- Main Form --}}
             <form action="{{ $formAction }}" method="POST" class="space-y-6">
                 @csrf
                 @if($isEditing)
                     @method('PUT')
                 @endif

                 {{-- Track Title --}}
                 <div class="form-control">
                     <x-input
                         id="title"
                         name="title"
                         type="text"
                         label="Track Name"
                         value="{{ old('title', $track?->title) }}"
                         required
                         helpText="The name of the track"
                         tooltip="Enter the title of the track as it should appear to users."
                         :error="$errors->first('title')"
                     />
                 </div>

                 {{-- Audio URL --}}
                 <div class="form-control">
                     <x-input
                         id="audio_url"
                         name="audio_url"
                         type="url"
                         label="Audio URL"
                         value="{{ old('audio_url', $track?->audio_url) }}"
                         required
                         helpText="Direct URL to an audio file (MP3, WAV, etc.)"
                         tooltip="Provide a direct link to the audio file. Should end with .mp3, .wav, etc."
                         tooltipPosition="right"
                         :error="$errors->first('audio_url')"
                     />
                 </div>

                 {{-- Cover Image URL --}}
                 <div class="form-control">
                     <x-input
                         id="image_url"
                         name="image_url"
                         type="url"
                         label="Cover Image URL"
                         value="{{ old('image_url', $track?->image_url) }}"
                         helpText="Direct URL to an image file (JPG, PNG, etc.)"
                         tooltip="Add a URL to the album art. Square images work best."
                         :error="$errors->first('image_url')"
                     />
                 </div>

                 {{-- Duration --}}
                 <div class="form-control">
                     <x-input
                         id="duration"
                         name="duration"
                         type="text"
                         label="Duration"
                         value="{{ old('duration', $track?->duration) }}"
                         placeholder="3:30"
                         helpText="Track duration in minutes:seconds format (e.g., 3:45)"
                         tooltip="Enter the duration in mm:ss format."
                         :error="$errors->first('duration')"
                     />
                 </div>

                 {{-- Genres Checkboxes --}}
                 <div class="form-control">
                     <x-label-with-tooltip
                         for="genre_ids" {{-- Link to the group --}}
                         value="Genres"
                         required
                         tooltip="Select at least one genre for the track."
                         tooltipPosition="top"
                         class="mb-2"
                     />
                     <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-4 border border-base-300 rounded-lg max-h-60 overflow-y-auto bg-base-200/50">
                         @php
                             // Ensure $genres is available (passed from controller for create)
                             $availableGenres = $genres ?? \App\Models\Genre::orderBy('name')->get();
                             $selectedGenres = old('genre_ids', $isEditing ? $track->genres->pluck('id')->toArray() : []);
                         @endphp
                         @forelse($availableGenres as $genre)
                             <label class="label cursor-pointer justify-start space-x-3 p-2 hover:bg-base-300/50 rounded-md">
                                 <input
                                     type="checkbox"
                                     name="genre_ids[]"
                                     value="{{ $genre->id }}"
                                     class="checkbox checkbox-primary checkbox-sm"
                                     {{ in_array($genre->id, $selectedGenres) ? 'checked' : '' }}
                                 />
                                 <span class="label-text">{{ $genre->name }}</span>
                             </label>
                         @empty
                              <p class="text-base-content/70 italic col-span-full text-center py-4">No genres found. Please add genres first.</p>
                         @endforelse
                     </div>
                     @if($errors->has('genre_ids') || $errors->has('genres'))
                         <label class="label">
                             <span class="label-text-alt text-error">{{ $errors->first('genre_ids') ?: $errors->first('genres') }}</span>
                          </label>
                     @endif
                     <label class="label">
                         <span class="label-text-alt text-base-content/70">Select all applicable genres.</span>
                      </label>
                 </div>

                 {{-- Action Buttons --}}
                 <div class="flex justify-end items-center pt-6 space-x-3">
                      <x-button
                         href="{{ route('tracks.index') }}"
                         variant="ghost"
                         size="sm"
                     >
                          <x-icon name="x-mark" class="mr-1 h-4 w-4" />
                         Cancel
                     </x-button>
                     <x-tooltip text="Save the current track information" position="top">
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
    </div>

    {{-- Bulk Import Form Panel (Only shown in Create mode) --}}
    @if(!$isEditing)
        <div x-show="activeTab === 'bulk'" x-cloak>
            <div class="card bg-base-100 shadow-xl">
                {{-- Reuse bulk form structure from old create.blade.php --}}
                <form action="{{ route('tracks.bulk-upload') }}" method="POST" class="card-body space-y-4">
                    @csrf
                    <h2 class="card-title">Bulk Import Tracks</h2>
                    <div class="form-control">
                        <label for="bulk_tracks" class="label">
                            <span class="label-text">Paste Track List</span>
                            <span class="label-text-alt">Format: Name|AudioUrl|ImageUrl|Genres</span>
                        </label>
                        <textarea 
                            name="bulk_tracks" 
                            id="bulk_tracks" 
                            rows="10" 
                            class="textarea textarea-bordered w-full font-mono text-sm @error('bulk_tracks') textarea-error @enderror" 
                            required
                            placeholder="My Track|https://.../track.mp3|https://.../cover.jpg|Genre1, Genre2\nAnother Track|https://.../track2.mp3||Electronic"
                        >{{ old('bulk_tracks') }}</textarea>
                        
                        @error('bulk_tracks')
                            <label class="label">
                               <span class="label-text-alt text-error">{{ $message }}</span>
                           </label>
                        @enderror
                        
                        <div class="mt-2 text-xs opacity-70 space-y-1">
                            <p>Each line should contain the following fields separated by pipes (|): <code class="font-bold">Track Name|Audio URL|Image URL|Genres</code> (Image URL is optional, Genres are comma-separated).</p>
                        </div>
                    </div>

                    <div class="card-actions justify-end pt-4">
                       <x-button type="button" @click="activeTab = 'single'" variant="ghost" size="sm"> {{-- Changed to button to switch tab --}}
                           <x-icon name="x-mark" size="4" class="mr-1" />
                            Cancel
                       </x-button>
                       <x-button type="submit" variant="primary" size="sm">
                            <x-icon name="upload" size="4" class="mr-1" />
                            Import Tracks
                       </x-button>
                   </div>
               </form>
            </div>
        </div>
    @endif
</div>
@endsection 