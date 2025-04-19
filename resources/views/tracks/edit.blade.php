@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">Edit Track: {{ $track->title }}</h1>
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

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <x-tracks-form 
                :track="$track"
                :submitRoute="route('tracks.update', $track)"
                submitMethod="PUT"
            />
        </div>
    </div>
</div>
@endsection
