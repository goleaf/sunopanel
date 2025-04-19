@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <x-page-header title="Edit Track: {{ $track->title }}">
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

    <div class="bg-white rounded-lg p-6 shadow-md">
        <x-tracks-form 
            :track="$track"
            :submitRoute="route('tracks.update', $track)"
            submitMethod="PUT"
        />
    </div>
</div>
@endsection
