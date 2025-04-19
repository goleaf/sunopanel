@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">Add New Track</h1>
        <x-button href="{{ route('tracks.index') }}" color="ghost">
            <x-icon name="back" size="5" class="mr-2" />
            Back to Tracks
        </x-button>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            <x-icon name="check" size="6" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            <x-icon name="x" size="6" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="mb-6">
        <div x-data="{ activeTab: 'single' }" class="mb-6">
            <div class="tabs tabs-bordered">
                <button type="button" @click="activeTab = 'single'"
                        :class="activeTab === 'single' ? 'tab-active' : ''"
                        class="tab tab-bordered">
                    Single Track
                </button>
                <button type="button" @click="activeTab = 'bulk'"
                        :class="activeTab === 'bulk' ? 'tab-active' : ''"
                        class="tab tab-bordered">
                    Bulk Import
                </button>
            </div>

            <div x-show="activeTab === 'single'" class="mt-4">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <x-tracks-form :submitRoute="route('tracks.store')" />
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'bulk'" class="mt-4">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <form action="{{ route('tracks.bulk-upload') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label for="bulk_tracks" class="label">
                                    <span class="label-text">Paste Track List (Format: Name|AudioUrl|ImageUrl|Genres)</span>
                                </label>
                                <textarea 
                                    name="bulk_tracks" 
                                    id="bulk_tracks" 
                                    rows="15"
                                    class="textarea textarea-bordered w-full @error('bulk_tracks') textarea-error @enderror" 
                                    required
                                >{{ old('bulk_tracks') }}</textarea>
                                @error('bulk_tracks')
                                    <div class="text-error text-sm mt-1">{{ $message }}</div>
                                @enderror
                                
                                <div class="mt-2 text-sm opacity-70">
                                    <p>Each line should contain the following fields separated by pipes (|):</p>
                                    <p class="font-mono mt-1">Track Name|Audio URL|Image URL|Genres</p>
                                    <p class="mt-1">Example:</p>
                                    <p class="font-mono text-xs mt-1">My Awesome Track|https://example.com/track.mp3|https://example.com/cover.jpg|Rock, Electronic</p>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <x-button href="{{ route('tracks.index') }}" color="ghost" class="mr-2">
                                    Cancel
                                </x-button>
                                <x-button type="submit" color="primary">
                                    <x-icon name="upload" size="5" class="mr-2" />
                                    Import Tracks
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
