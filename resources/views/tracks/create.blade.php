@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">Add New Track</h1>
        <a href="{{ route('tracks.index') }}" class="btn btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Back to Tracks
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
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
                                <a href="{{ route('tracks.index') }}" class="btn btn-ghost mr-2">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Import Tracks
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
