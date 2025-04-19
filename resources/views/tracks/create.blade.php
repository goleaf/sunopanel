@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <x-page-header title="Add New Track">
        <x-slot name="buttons">
            <x-button href="{{ route('tracks.index') }}" variant="light">
                <x-icon name="arrow-left" class="-ml-1 mr-2 h-5 w-5" />
                Back to Tracks
            </x-button>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success" :message="session('success')" class="mb-4" />
    @endif

    @if(session('error'))
        <x-alert type="error" :message="session('error')" class="mb-4" />
    @endif

    <div class="mb-6">
        <div x-data="{ activeTab: 'single' }" class="mb-6">
            <div class="flex border-b border-gray-200">
                <button type="button" @click="activeTab = 'single'"
                        :class="{ 'border-b-2 border-indigo-500 text-indigo-600': activeTab === 'single', 'text-gray-500 hover:text-gray-700': activeTab !== 'single' }"
                        class="px-4 py-2 font-medium">
                    Single Track
                </button>
                <button type="button" @click="activeTab = 'bulk'"
                        :class="{ 'border-b-2 border-indigo-500 text-indigo-600': activeTab === 'bulk', 'text-gray-500 hover:text-gray-700': activeTab !== 'bulk' }"
                        class="px-4 py-2 font-medium">
                    Bulk Import
                </button>
            </div>

            <div x-show="activeTab === 'single'" class="mt-4 bg-white rounded-lg p-6 shadow-md">
                <x-tracks-form :submitRoute="route('tracks.store')" />
            </div>

            <div x-show="activeTab === 'bulk'" class="mt-4">
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <form action="{{ route('tracks.store') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <x-label for="bulk_tracks" value="Paste Track List (Format: Name|AudioUrl|ImageUrl|Genres)" />
                                <textarea 
                                    name="bulk_tracks" 
                                    id="bulk_tracks" 
                                    rows="15"
                                    class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm @error('bulk_tracks') border-red-500 @enderror" 
                                    required
                                >{{ old('bulk_tracks') }}</textarea>
                                <x-input-error for="bulk_tracks" class="mt-2" />
                                
                                <div class="mt-2 text-sm text-gray-600">
                                    <p>Each line should contain the following fields separated by pipes (|):</p>
                                    <p class="font-mono mt-1">Track Name|Audio URL|Image URL|Genres</p>
                                    <p class="mt-1">Example:</p>
                                    <p class="font-mono text-xs mt-1">My Awesome Track|https://example.com/track.mp3|https://example.com/cover.jpg|Rock, Electronic</p>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <x-button href="{{ route('tracks.index') }}" color="white" class="mr-2">
                                    Cancel
                                </x-button>
                                <x-button type="submit" color="indigo">
                                    <x-icon name="upload" class="-ml-1 mr-2 h-5 w-5" />
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
